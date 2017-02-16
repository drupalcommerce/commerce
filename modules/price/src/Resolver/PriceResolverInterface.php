<?php

namespace Drupal\commerce_price\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the interface for base price resolvers.
 */
interface PriceResolverInterface {

  /**
   * Resolves the base price of a given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_price\Price|null
   *   A price value object, if resolved. Otherwise NULL, indicating that the
   *   next resolver in the chain should be called.
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context);

}
