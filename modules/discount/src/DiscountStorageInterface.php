<?php

namespace Drupal\commerce_discount;

use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Defines the interface for discount storage.
 */
interface DiscountStorageInterface {

  /**
   * Loads the valid discounts for the given order type and store.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface $order_type
   *   The order type.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_discount\Entity\DiscountInterface[]
   *   The valid discounts.
   */
  public function loadValid(OrderTypeInterface $order_type, StoreInterface $store);

}
