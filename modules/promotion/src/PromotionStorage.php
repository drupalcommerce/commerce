<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the promotion storage.
 */
class PromotionStorage extends CommerceContentEntityStorage implements PromotionStorageInterface {

  /**
   * The usage.
   *
   * @var \Drupal\commerce_promotion\PromotionUsageInterface
   */
  protected $usage;

  /**
   * Constructs a new PromotionStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_promotion\PromotionUsageInterface $usage
   *   The usage.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher, PromotionUsageInterface $usage) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $event_dispatcher);

    $this->usage = $usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_promotion.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadAvailable(OrderTypeInterface $order_type, StoreInterface $store) {
    $query = $this->getQuery();
    $or_condition = $query->orConditionGroup()
      ->condition('end_date', gmdate('Y-m-d'), '>=')
      ->notExists('end_date', gmdate('Y-m-d'));
    $query
      ->condition('stores', [$store->id()], 'IN')
      ->condition('order_types', [$order_type->id()], 'IN')
      ->condition('start_date', gmdate('Y-m-d'), '<=')
      ->condition('status', TRUE)
      ->condition($or_condition);
    // Only load promotions without coupons. Promotions with coupons are loaded
    // coupon-first in a different process.
    $query->notExists('coupons');
    $query->sort('weight', 'ASC');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    $promotions = $this->loadMultiple($result);
    // Remove any promotions that have hit their usage limit.
    $promotions_with_usage_limits = array_filter($promotions, function ($promotion) {
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
      return !empty($promotion->getUsageLimit());
    });
    $usages = $this->usage->getUsageMultiple($promotions_with_usage_limits);
    foreach ($promotions_with_usage_limits as $promotion_id => $promotion) {
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
      if ($promotion->getUsageLimit() <= $usages[$promotion_id]) {
        unset($promotions[$promotion_id]);
      }
    }

    return $promotions;
  }

  /**
   * Return all active promotions that are no longer valid determined by their
   * end date.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   The expired promotion entities.
   */
  public function loadExpired() {
    $query = $this->getQuery();

    $query
      ->condition('end_date', gmdate('Y-m-d'), '<')
      ->condition('status', TRUE);

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    return $this->loadMultiple($result);
  }

  /**
   * Returns any promotions which are still active and have a met their maximum
   * usage.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   Promotions with maxed usage.
   */
  public function loadMaxedUsage() {
    $query = $this->getQuery();

    $query
      ->condition('usage_limit', 1, '>=')
      ->condition('status', TRUE);

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $promotions = $this->loadMultiple($result);
    $maxed_promotions = [];

    // Get an array of each promotion's use count.
    $promotion_uses = $this->usage->getUsageMultiple($promotions);

    foreach ($promotions as $promotion) {
      if ($promotion_uses[$promotion->id()] >= $promotion->getUsageLimit()) {
        $maxed_promotions[] = $promotion;
      }
    }

    return $maxed_promotions;
  }

}
