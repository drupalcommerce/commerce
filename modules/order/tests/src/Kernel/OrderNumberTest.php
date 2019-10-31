<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderType;
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

    $number_pattern = NumberPattern::load('order_default');
    $number_pattern->setPluginConfiguration([
      'initial_number' => 102,
      'padding' => 4,
    ] + $number_pattern->getPluginConfiguration());
    $number_pattern->save();

    $first_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $first_order_item->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $first_order */
    $first_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$first_order_item],
    ]);
    $first_order->save();

    // Confirm that the number pattern was used to generate an order number.
    $first_order->getState()->applyTransitionById('place');
    $first_order->save();
    $this->assertEquals('0102', $first_order->getOrderNumber());

    $order_type = OrderType::load('default');
    $order_type->setNumberPatternId('INVALID');
    $order_type->save();

    $second_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $second_order_item->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $first_order */
    $second_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$second_order_item],
    ]);
    $second_order->save();

    // Confirm that the order number was set to the order ID.
    $second_order->getState()->applyTransitionById('place');
    $second_order->save();
    $this->assertEquals($second_order->id(), $second_order->getOrderNumber());

    $third_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('14.00', 'USD'),
    ]);
    $third_order_item->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order2 */
    $third_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '9999',
      'order_items' => [$third_order_item],
    ]);
    $third_order->save();

    // Confirm that an explicitly set order number was not overridden.
    $third_order->getState()->applyTransitionById('place');
    $third_order->save();
    $this->assertEquals('9999', $third_order->getOrderNumber());
  }

}
