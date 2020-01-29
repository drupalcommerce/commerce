<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the interface for promotion storage.
 */
interface PromotionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the available promotions for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string[] $offer_ids
   *   (Optional) A list of offer IDs to filter by.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface[]
   *   The available promotions.
   */
  public function loadAvailable(OrderInterface $order, array $offer_ids = []);

}
