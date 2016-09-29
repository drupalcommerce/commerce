<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;

/**
 * Tests the order account (owner) functionality.
 *
 * @group commerce
 */
class OrderAccountTest extends OrderBrowserTestBase {

  /**
   * Tests assigning guest orders to newly created users (after checkout).
   */
  public function testNewAccountOwnsOrderAfterGuestCheckout() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'product_variation',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => 'guest@example.com',
      'order_items' => [$order_item],
    ]);
    $order = Order::load($order->id());

    $this->assertEquals(0, $order->getOwnerId(), 'The guest order has no owner account.');
    $user = $this->createUser([], 'guest');
    $this->assertEquals($order->getOwnerId(), $user->id(), 'New user account owns previous guest order.');
  }

}
