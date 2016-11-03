<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;

/**
 * Tests the commerce_order entity forms.
 *
 * @group commerce
 */
class OrderTest extends OrderBrowserTestBase {

  /**
   * Tests creating an order programaticaly and through the UI.
   */
  public function testCreateOrder() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
    ]);

    $order_exists = (bool) Order::load($order->id());
    $this->assertTrue($order_exists, 'The new order has been created in the database.');
    $this->assertEquals($order->id(), $order->getOrderNumber(), 'The order number matches the order ID');
  }

  /**
   * Tests deleting an order programaticaly and through the UI.
   */
  public function testDeleteOrder() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
    ]);
    $order->delete();

    $order_exists = (bool) Order::load($order->id());
    $order_item_exists = (bool) OrderItem::load($order_item->id());
    $this->assertFalse($order_exists, 'The new order has been deleted from the database.');
    $this->assertFalse($order_item_exists, 'The matching order item has been deleted from the database.');
  }

  /**
   * Tests the generation of the 'placed' and 'completed' timestamps.
   */
  public function testOrderTimestamps() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
    ]);
    $order->save();
    $this->assertNull($order->getPlacedTime());
    $this->assertNull($order->getCompletedTime());
    // Transitioning the order out of the draft state should set the timestamps.
    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();
    $this->assertEquals($order->getPlacedTime(), REQUEST_TIME);
    $this->assertEquals($order->getCompletedTime(), REQUEST_TIME);
  }

}
