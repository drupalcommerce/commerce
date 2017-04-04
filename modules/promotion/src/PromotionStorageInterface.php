<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for promotion storage.
 */
interface PromotionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the available promotions for the given order type and store.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface $order_type
   *   The order type.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface[]
   *   The available promotions.
   */
  public function loadAvailable(OrderTypeInterface $order_type, StoreInterface $store);

  /**
   * Builds a query that will load all promotions that are no longer valid
   * determined by the base field limiting values.
   *
   * @param bool $only_enabled
   *   Only include currently enabled promotions that have expired.
   * @return array|bool|\Drupal\Core\Entity\EntityInterface[]
   *   The expired promotion entities. Returns FALSE if none found.
   */
  public function loadExpired($only_enabled);

}
