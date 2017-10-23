<?php

namespace Drupal\commerce_order;

use Drupal\commerce\PurchasableEntityInterface;

interface PurchasableEntityPriceCalculatorInterface {

  /**
   * Calculates a purchasable entity's price.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param array $adjustment_types
   *   The adjustment types to calculate.
   *
   * @return \Drupal\commerce_price\Price[] The calculated price.
   * The calculated price.
   */
  public function calculate(PurchasableEntityInterface $purchasable_entity, $quantity, array $adjustment_types = []);

}
