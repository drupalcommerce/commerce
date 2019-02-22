<?php

namespace Drupal\commerce_order_test;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Price;

/**
 * Adds order and order item adjustments for testing purposes.
 */
class TestAdjustmentProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      // Add adjustment for PriceCalculatorTest.
      if ($order->getEmail() == 'user2@example.com') {
        $order_item->addAdjustment(new Adjustment([
          'type' => 'test_adjustment_type',
          'label' => '$2.00 fee',
          'amount' => new Price('2.00', 'USD'),
        ]));
      }
    }
  }

}
