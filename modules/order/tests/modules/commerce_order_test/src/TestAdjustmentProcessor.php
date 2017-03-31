<?php

namespace Drupal\commerce_order_test;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;

/**
 * Adjustment processor to attach adjustments during order refresh during tests.
 */
class TestAdjustmentProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order->getData('apply_test_adjustments')) {
      foreach ($order->getItems() as $order_item) {
        $test_adjustments = $order_item->getData('test_adjustments', []);
        foreach ($test_adjustments as $test_adjustment) {
          $order_item->addAdjustment($test_adjustment);
        }
      }

      $test_adjustments = $order->getData('test_adjustments', []);
      foreach ($test_adjustments as $test_adjustment) {
        $order->addAdjustment($test_adjustment);
      }
    }
  }

}
