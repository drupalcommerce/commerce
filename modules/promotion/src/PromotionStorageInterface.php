<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the interface for promotion storage.
 */
interface PromotionStorageInterface extends EntityStorageInterface {

  /**
   * Loads the valid promotions for the given order type and store.
   *
   * @param string $order_type
   *   The order type.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface[]
   *   The valid promotions.
   */
  public function loadValid($order_type, StoreInterface $store);

  /**
   * Loads the valid promotions for the given coupon.
   *
   * @param string $order_type
   *   The order type.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface
   *   The valid promotions.
   */
  public function loadByCoupon($order_type, StoreInterface $store, CouponInterface $coupon);

}
