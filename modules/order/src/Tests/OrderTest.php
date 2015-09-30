<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\LineItem;

/**
 * Tests the commerce_order entity forms.
 *
 * @group commerce
 */
class OrderTest extends OrderTestBase {

  /**
   * Tests creating an order programaticaly and through the UI.
   */
  public function testCreateOrder() {
    $lineItem = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$lineItem],
    ]);

    $orderExists = (bool) Order::load($order->id());
    $this->assertTrue($orderExists, 'The new order has been created in the database.');
    $this->assertEqual($order->id(), $order->getOrderNumber(), 'The order number matches the order ID');
  }

  /**
   * Tests deleting an order programaticaly and through the UI.
   */
  public function testDeleteOrder() {
    $lineItem = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$lineItem],
    ]);
    $order->delete();

    $orderExists = (bool) Order::load($order->id());
    $lineItemExists = (bool) LineItem::load($lineItem->id());
    $this->assertFalse($orderExists, 'The new order has been deleted from the database.');
    $this->assertFalse($lineItemExists, 'The matching line item has been deleted from the database.');
  }

}
