<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Tests the order access control handler.
 *
 * @group commerce
 */
class OrderAccessControlHandlerTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_test',
  ];

  /**
   * Tests the access checking.
   */
  public function testOrderAccess() {
    $admin_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['administer commerce_order']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['view own commerce_order']);
    $different_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['view own commerce_order']);
    $anon_user = new AnonymousUserSession();

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$order_item],
    ]);
    $order->save();

    // Tests the 'view own commerce_order' access checking.
    $this->assertTrue($order->access('view', $user));
    $this->assertFalse($order->access('view', $different_user));
    $this->assertTrue($order->access('view', $admin_user));
    $this->assertFalse($order->access('view', $anon_user));

    // Tests the access checking for resending order receipts.
    $this->assertFalse($order->access('resend_receipt', $user));
    $this->assertFalse($order->access('resend_receipt', $different_user));
    $this->assertFalse($order->access('resend_receipt', $admin_user));
    $transition = $order->getState()->getTransitions();
    $order->getState()->applyTransition($transition['place']);
    $order->save();
    \Drupal::entityTypeManager()->getAccessControlHandler('commerce_order')->resetCache();
    $this->assertFalse($order->access('resend_receipt', $user));
    $this->assertFalse($order->access('resend_receipt', $different_user));
    $this->assertTrue($order->access('resend_receipt', $admin_user));

    // Tests the access checking for locked orders.
    $this->assertTrue($order->access('update', $admin_user));
    $this->assertTrue($order->access('delete', $admin_user));
    $this->assertFalse($order->access('unlock', $admin_user));
    $order->lock();
    \Drupal::entityTypeManager()->getAccessControlHandler('commerce_order')->resetCache();
    $this->assertFalse($order->access('update', $admin_user));
    $this->assertFalse($order->access('delete', $admin_user));
    $this->assertTrue($order->access('unlock', $admin_user));
  }

}
