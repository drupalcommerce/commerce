<?php

namespace Drupal\commerce_price_test;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Test price resolver.
 */
class TestPriceResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    if ($entity instanceof ProductVariationInterface && strpos($entity->getSku(), 'TEST_') !== FALSE) {
      return $entity->getPrice()->subtract(new Price('3', $entity->getPrice()->getCurrencyCode()));
    }
    return NULL;
  }

}
