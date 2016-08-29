<?php

namespace Drupal\commerce_price\Resolver;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Returns the price based on the purchasable entity's price field.
 */
class DefaultPriceResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity = 1) {
    return $entity->getPrice();
  }

}
