<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

/**
 * Tests the order item access control.
 *
 * @coversDefaultClass \Drupal\commerce_order\OrderItemAccessControlHandler
 * @group commerce
 */
class OrderItemAccessTest extends OrderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'canceled',
      'order_items' => [$order_item],
    ]);
    $order->save();
    $order_item = $this->reloadEntity($order_item);

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($order_item->access('view', $account));
    $this->assertFalse($order_item->access('update', $account));
    $this->assertFalse($order_item->access('delete', $account));

    $account = $this->createUser([], ['view commerce_order']);
    $this->assertTrue($order_item->access('view', $account));
    $this->assertFalse($order_item->access('update', $account));
    $this->assertFalse($order_item->access('delete', $account));

    $account = $this->createUser([], ['update default commerce_order']);
    $this->assertFalse($order_item->access('view', $account));
    $this->assertFalse($order_item->access('update', $account));
    $this->assertFalse($order_item->access('delete', $account));

    $account = $this->createUser([], [
      'manage test commerce_order_item',
    ]);
    $this->assertFalse($order_item->access('view', $account));
    $this->assertTrue($order_item->access('update', $account));
    $this->assertTrue($order_item->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_order']);
    $this->assertTrue($order_item->access('view', $account));
    $this->assertTrue($order_item->access('update', $account));
    $this->assertTrue($order_item->access('delete', $account));

    // Broken order reference.
    $order_item->set('order_id', '999');
    $account = $this->createUser([], ['manage test commerce_order_item']);
    $this->assertFalse($order_item->access('view', $account));
    $this->assertFalse($order_item->access('update', $account));
    $this->assertFalse($order_item->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_order_item');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('test', $account));

    $account = $this->createUser([], ['administer commerce_order']);
    $this->assertTrue($access_control_handler->createAccess('test', $account));

    $account = $this->createUser([], ['manage test commerce_order_item']);
    $this->assertTrue($access_control_handler->createAccess('test', $account));
  }

}
