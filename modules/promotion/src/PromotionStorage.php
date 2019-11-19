<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
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
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PromotionStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_promotion\PromotionUsageInterface $usage
   *   The usage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, PromotionUsageInterface $usage, TimeInterface $time) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager, $event_dispatcher);

    $this->usage = $usage;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_promotion.usage'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadAvailable(OrderInterface $order) {
    $date = $order->getCalculationDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $query = $this->getQuery();
    $or_condition = $query->orConditionGroup()
      ->condition('end_date', $date, '>')
      ->notExists('end_date');
    $query
      ->condition('stores', [$order->getStoreId()], 'IN')
      ->condition('order_types', [$order->bundle()], 'IN')
      ->condition('start_date', $date, '<=')
      ->condition('status', TRUE)
      ->condition($or_condition);
    // Only load promotions without coupons. Promotions with coupons are loaded
    // coupon-first in a different process.
    $query->notExists('coupons');
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
    $usages = $this->usage->loadMultiple($promotions_with_usage_limits);
    foreach ($promotions_with_usage_limits as $promotion_id => $promotion) {
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
      if ($promotion->getUsageLimit() <= $usages[$promotion_id]) {
        unset($promotions[$promotion_id]);
      }
    }
    // Sort the remaining promotions.
    uasort($promotions, [$this->entityType->getClass(), 'sort']);

    return $promotions;
  }

}
