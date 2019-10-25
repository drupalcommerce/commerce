<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the payment access control.
 *
 * @coversDefaultClass \Drupal\commerce_payment\PaymentAccessControlHandler
 * @group commerce
 */
class PaymentAccessTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_payment');
    $this->installConfig(['commerce_payment']);

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);
    $order_item->save();

    $this->order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'order_items' => [$order_item],
      'state' => 'draft',
      'payment_gateway' => 'onsite',
    ]);
    $this->order->save();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
    $regular_user = $this->createUser(['uid' => 2]);
    \Drupal::currentUser()->setAccount($regular_user);
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'mode' => 'live',
      ],
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => $payment_gateway->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('39.99', 'USD'),
      'state' => 'completed',
    ]);
    $payment->save();

    $insufficient_permissions = [
      'access administration pages',
      'view default commerce_order',
      'administer commerce_payment',
    ];
    foreach ($insufficient_permissions as $insufficient_permission) {
      $account = $this->createUser([], [$insufficient_permission]);
      $this->assertFalse($payment->access('view', $account));
      $this->assertFalse($payment->access('delete', $account));
      $this->assertFalse($payment->access('capture', $account));
      $this->assertFalse($payment->access('refund', $account));
    }

    $account = $this->createUser([], [
      'administer commerce_payment',
      'view default commerce_order',
    ]);
    $this->assertTrue($payment->access('view', $account));
    $this->assertFalse($payment->access('delete', $account));
    $this->assertFalse($payment->access('capture', $account));
    $this->assertTrue($payment->access('refund', $account));

    // Payments can be deleted if they were made in test mode.
    $account = $this->createUser([], [
      'administer commerce_payment',
      'view default commerce_order',
    ]);
    $payment->set('payment_gateway_mode', 'test');
    $this->assertTrue($payment->access('delete', $account));

    // Gateway-specific operation access (e.g. "refund") is denied if the
    // gateway is missing.
    $payment_gateway->delete();
    $payment = $this->reloadEntity($payment);
    $account = $this->createUser([], [
      'administer commerce_payment',
      'view default commerce_order',
    ]);
    $this->assertTrue($payment->access('view', $account));
    $this->assertFalse($payment->access('delete', $account));
    $this->assertFalse($payment->access('capture', $account));
    $this->assertFalse($payment->access('refund', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_payment');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('payment_default', $account));

    $account = $this->createUser([], ['administer commerce_payment']);
    $this->assertTrue($access_control_handler->createAccess('payment_default', $account));
  }

}
