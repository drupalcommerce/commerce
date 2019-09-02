<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for coupons.
 */
class CouponListBuilder extends EntityListBuilder {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The usage.
   *
   * @var \Drupal\commerce_promotion\PromotionUsageInterface
   */
  protected $usage;

  /**
   * The usage counts.
   *
   * @var array
   */
  protected $usageCounts;

  /**
   * Constructs a new CouponListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\commerce_promotion\PromotionUsageInterface $usage
   *   The usage.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, PromotionUsageInterface $usage) {
    parent::__construct($entity_type, $storage);

    $this->routeMatch = $route_match;
    $this->usage = $usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('commerce_promotion.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $promotion = $this->routeMatch->getParameter('commerce_promotion');
    $coupons = $this->storage->loadMultipleByPromotion($promotion);
    // Load the usage counts for each coupon.
    $this->usageCounts = $this->usage->loadMultipleByCoupon($coupons);

    return $coupons;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['code'] = $this->t('Code');
    $header['usage'] = $this->t('Usage');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $entity */
    $current_usage = $this->usageCounts[$entity->id()];
    $usage_limit = $entity->getUsageLimit();
    $usage_limit = $usage_limit ?: $this->t('Unlimited');
    $row['code'] = $entity->label();
    if (!$entity->isEnabled()) {
      $row['code'] .= ' (' . $this->t('Disabled') . ')';
    }
    $row['usage'] = $current_usage . ' / ' . $usage_limit;

    return $row + parent::buildRow($entity);
  }

}
