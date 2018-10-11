<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the setting of the placed timestamp during order placement.
 *
 * @group commerce
 */
class TimestampEventTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
  }

  /**
   * Tests setting the order number.
   */
  public function testOnPlaceTransition() {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
    ]);
    $order->save();

    $transition = $order->getState()->getTransitions();
    $order->getState()->applyTransition($transition['place']);
    $order->save();
    $this->assertEquals(\Drupal::time()->getRequestTime(), $order->getPlacedTime(), 'During placement transition, the order placed timestamp is set to the request time.');
  }

}
