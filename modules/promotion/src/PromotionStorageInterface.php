<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Defines the interface for promotion storage.
 */
interface PromotionStorageInterface {

  /**
   * Loads the valid promotions for the given order type and store.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface $order_type
   *   The order type.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface[]
   *   The valid promotions.
   */
  public function loadValid(OrderTypeInterface $order_type, StoreInterface $store);

}
