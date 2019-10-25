<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

/**
 * Tests the setting of the order number during order placement.
 *
 * @group commerce
 */
class OrderNumberTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_test',
  ];

  /**
   * Tests setting the order number.
   */
  public function testSetOrderNumber() {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    $order_item1 = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item1->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order1 */
    $order1 = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$order_item1],
    ]);
    $order1->save();

    $order1->getState()->applyTransitionById('place');
    $order1->save();
    $this->assertEquals($order1->id(), $order1->getOrderNumber(), 'During placement transition, the order number is set to the order ID.');

    $order_item2 = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('14.00', 'USD'),
    ]);
    $order_item2->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order2 */
    $order2 = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '9999',
      'order_items' => [$order_item2],
    ]);
    $order2->save();

    $order2->getState()->applyTransitionById('place');
    $order2->save();
    $this->assertEquals('9999', $order2->getOrderNumber(), 'Explicitly set order number should not get overridden.');
  }

}
