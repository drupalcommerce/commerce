<?php

namespace Drupal\commerce_order_test;

use Drupal\commerce\Context;
use Drupal\commerce_order\AvailabilityCheckerInterface;
use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Test availability checker.
 */
class TestAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();
    return $purchased_entity instanceof ProductVariationInterface && strpos($purchased_entity->getSku(), 'TEST_') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) {
    return AvailabilityResult::unavailable();
  }

}
