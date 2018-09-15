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
    if (!($entity instanceof ProductVariationInterface && strpos($entity->getSku(), 'TEST_') !== FALSE)) {
      return NULL;
    }

    $field_name = $context->getData('field_name', 'price');
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      /** @var \Drupal\commerce_price\Price $price */
      $price = $entity->get($field_name)->first()->toPrice();
      return $price->subtract(new Price('3', $price->getCurrencyCode()));
    }
  }

}
