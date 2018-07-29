<?php

namespace Drupal\commerce_price\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Provides the default price, taking it directly from the purchasable entity.
 */
class DefaultPriceResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $field_name = $context->getData('field_name', 'price');
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      return $entity->get($field_name)->first()->toPrice();
    }
  }

}
