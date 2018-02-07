<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

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
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
    ]);

    $order_exists = (bool) Order::load($order->id());
    $this->assertNotEmpty($order_exists, 'The new order has been created in the database.');
  }

  /**
   * Tests deleting an order programaticaly and through the UI.
   */
  public function testDeleteOrder() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
    ]);
    $order->delete();

    $order_exists = (bool) Order::load($order->id());
    $order_item_exists = (bool) OrderItem::load($order_item->id());
    $this->assertEmpty($order_exists, 'The new order has been deleted from the database.');
    $this->assertEmpty($order_item_exists, 'The matching order item has been deleted from the database.');
  }

  /**
   * Tests the generation of the 'placed' and 'completed' timestamps.
   */
  public function testOrderTimestamps() {
    $customer = $this->createUser();
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'store_id' => $this->store->id(),
      'uid' => $customer,
      'order_items' => [$order_item],
    ]);
    $order->save();
    $this->assertNull($order->getPlacedTime());
    $this->assertNull($order->getCompletedTime());
    // Transitioning the order out of the draft state should set the timestamps.
    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();
    $this->assertEquals($order->getPlacedTime(), \Drupal::time()->getRequestTime());
    $this->assertEquals($order->getCompletedTime(), \Drupal::time()->getRequestTime());
  }

}
