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
    $line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$line_item],
    ]);

    $order_exists = (bool) Order::load($order->id());
    $this->assertTrue($order_exists, 'The new order has been created in the database.');
    $this->assertEqual($order->id(), $order->getOrderNumber(), 'The order number matches the order ID');
  }

  /**
   * Tests deleting an order programaticaly and through the UI.
   */
  public function testDeleteOrder() {
    $line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$line_item],
    ]);
    $order->delete();

    $order_exists = (bool) Order::load($order->id());
    $line_item_exists = (bool) LineItem::load($line_item->id());
    $this->assertFalse($order_exists, 'The new order has been deleted from the database.');
    $this->assertFalse($line_item_exists, 'The matching line item has been deleted from the database.');
  }

  /**
   * Tests the generation of the 'placed' timestamp.
   */
  public function testOrderPlaced() {
    $line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$line_item],
    ]);

    $this->assertNull($order->getPlacedTime());
    $order->save();
    $this->assertNull($order->getPlacedTime());
    // Transitioning the order out of the draft state should set the timestamp.
    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();
    $this->assertEqual($order->getPlacedTime(), REQUEST_TIME);
  }

}
