<?php

namespace Drupal\Tests\commerce_order\Functional;

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
      'uid' => 0,
      'mail' => 'guest@example.com',
      'order_items' => [$order_item],
    ]);
    $order->save();
    $this->assertEmpty($order->getOwnerId(), 'The guest order has no owner account.');
    $user = $this->createUser([], 'guest');
    $this->assertEquals($user->id(), $order->getOwnerId(), 'New user account owns previous guest order.');
  }

}
