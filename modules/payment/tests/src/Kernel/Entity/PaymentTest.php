<?php

namespace Drupal\Tests\commerce_payment\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentDefault;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the payment entity.
 *
 * @coversDefaultClass \Drupal\commerce_payment\Entity\Payment
 *
 * @group commerce
 */
class PaymentTest extends CommerceKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * @covers ::getType
   * @covers ::getPaymentGatewayId
   * @covers ::getPaymentGatewayMode
   * @covers ::getOrder
   * @covers ::getOrderId
   * @covers ::getRemoteId
   * @covers ::setRemoteId
   * @covers ::getRemoteState
   * @covers ::setRemoteState
   * @covers ::getBalance
   * @covers ::getAmount
   * @covers ::setAmount
   * @covers ::getRefundedAmount
   * @covers ::setRefundedAmount
   * @covers ::getState
   * @covers ::setState
   * @covers ::isExpired
   * @covers ::getExpiresTime
   * @covers ::setExpiresTime
   * @covers ::isCompleted
   * @covers ::getCompletedTime
   * @covers ::setCompletedTime
   */
  public function testPayment() {
    $order = Order::create([
      'type' => 'default',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    $order = $this->reloadEntity($order);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'example',
      'order_id' => $order,
      'amount' => new Price('30', 'USD'),
      'refunded_amount' => new Price('10', 'USD'),
      'state' => 'refunded',
    ]);
    $payment->save();

    $this->assertInstanceOf(PaymentDefault::class, $payment->getType());
    $this->assertEquals('example', $payment->getPaymentGatewayId());
    $this->assertEquals('test', $payment->getPaymentGatewayMode());

    $this->assertEquals($order, $payment->getOrder());
    $this->assertEquals($order->id(), $payment->getOrderId());

    $payment->setRemoteId('123456');
    $this->assertEquals('123456', $payment->getRemoteId());

    $payment->setRemoteState('pending');
    $this->assertEquals('pending', $payment->getRemoteState());

    $this->assertEquals(new Price('30', 'USD'), $payment->getAmount());
    $this->assertEquals(new Price('10', 'USD'), $payment->getRefundedAmount());
    $this->assertEquals(new Price('20', 'USD'), $payment->getBalance());

    $payment->setAmount(new Price('40', 'USD'));
    $this->assertEquals(new Price('40', 'USD'), $payment->getAmount());
    $payment->setRefundedAmount(new Price('15', 'USD'));
    $this->assertEquals(new Price('15', 'USD'), $payment->getRefundedAmount());

    $this->assertEquals('refunded', $payment->getState()->value);
    $payment->setState('completed');
    $this->assertEquals('completed', $payment->getState()->value);

    $this->assertFalse($payment->isExpired());
    $payment->setExpiresTime(635879700);
    $this->assertTrue($payment->isExpired());
    $this->assertEquals(635879700, $payment->getExpiresTime());

    $this->assertFalse($payment->isCompleted());
    $payment->setCompletedTime(635879700);
    $this->assertEquals(635879700, $payment->getCompletedTime());
    $this->assertTrue($payment->isCompleted());
  }

}
