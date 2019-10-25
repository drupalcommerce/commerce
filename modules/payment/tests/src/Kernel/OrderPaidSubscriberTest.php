<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the OrderPaidSubscriber.
 *
 * @coversDefaultClass \Drupal\commerce_payment\EventSubscriber\OrderPaidSubscriber
 *
 * @group commerce
 */
class OrderPaidSubscriberTest extends OrderKernelTestBase {

  /**
   * The sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installConfig('commerce_payment');

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
  }

  /**
   * Confirms that on-site payments do not affect the order status.
   */
  public function testOnsiteGateway() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $onsite_gateway */
    $onsite_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $onsite_gateway->save();
    $this->order->set('payment_gateway', $onsite_gateway);
    $this->order->save();

    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => $onsite_gateway->id(),
      'order_id' => $this->order->id(),
      'amount' => $this->order->getTotalPrice(),
      'state' => 'completed',
    ]);
    $payment->save();
    $this->order->save();

    $this->assertEquals('draft', $this->order->getState()->getId());
    $this->assertEmpty($this->order->getOrderNumber());
    $this->assertEmpty($this->order->getPlacedTime());
    $this->assertEmpty($this->order->getCompletedTime());
  }

  /**
   * Confirms that off-site payments result in the order getting placed.
   */
  public function testOffsiteGateway() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $offsite_gateway */
    $offsite_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $offsite_gateway->save();
    $this->order->set('payment_gateway', $offsite_gateway);
    $this->order->lock();
    $this->order->save();

    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => $offsite_gateway->id(),
      'order_id' => $this->order->id(),
      'amount' => $this->order->getTotalPrice(),
      'state' => 'completed',
    ]);
    $payment->save();
    $this->order->save();

    $this->assertEquals('completed', $this->order->getState()->getId());
    $this->assertFalse($this->order->isLocked());
    $this->assertNotEmpty($this->order->getOrderNumber());
    $this->assertNotEmpty($this->order->getPlacedTime());
    $this->assertNotEmpty($this->order->getCompletedTime());
  }

}
