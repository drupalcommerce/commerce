<?php

namespace Drupal\commerce_order_test;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Adds order and order item adjustments for testing purposes.
 */
class TestAdjustmentProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      $test_adjustments = $order_item->getData('test_adjustments', []);
      foreach ($test_adjustments as $test_adjustment) {
        $order_item->addAdjustment($test_adjustment);
      }

      // Add adjustment for PurchasableEntityPriceCalculatorTest.
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity instanceof ProductVariationInterface) {
        if ($purchased_entity->getSku() == 'TEST_CALCULATED_PRICE') {
          $order_item->addAdjustment(new Adjustment([
            'type' => 'test_adjustment_type',
            'label' => '$2.00 item fee',
            'amount' => new Price('2.00', 'USD'),
          ]));
        }
      }
    }

    $test_adjustments = $order->getData('test_adjustments', []);
    foreach ($test_adjustments as $test_adjustment) {
      $order->addAdjustment($test_adjustment);
    }
  }

}
