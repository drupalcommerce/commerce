<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the payment order updater.
 *
 * @coversDefaultClass \Drupal\commerce_payment\PaymentOrderUpdater
 *
 * @group commerce
 */
class PaymentOrderUpdaterTest extends OrderKernelTestBase {

  /**
   * The payment order updater.
   *
   * @var \Drupal\commerce_payment\PaymentOrderUpdaterInterface
   */
  protected $paymentOrderUpdater;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The first order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $firstOrder;

  /**
   * The second order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $secondOrder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_payment_example',
    'commerce_payment_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_payment');

    $this->paymentOrderUpdater = $this->container->get('commerce_payment.order_updater');
    $this->user = $this->createUser();

    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();

    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $this->user->id(),
    ]);
    $profile->save();

    $payment_method = PaymentMethod::create([
      'uid' => $this->user->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '1111',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->save();

    $first_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);
    $first_order_item->save();

    $first_order = Order::create([
      'uid' => $this->user,
      'type' => 'default',
      'state' => 'draft',
      'order_items' => [$first_order_item],
      'store_id' => $this->store,
    ]);
    $first_order->save();
    $this->firstOrder = $this->reloadEntity($first_order);

    $second_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('20', 'USD'),
    ]);
    $second_order_item->save();

    $second_order = Order::create([
      'uid' => $this->user,
      'type' => 'default',
      'state' => 'draft',
      'order_items' => [$second_order_item],
      'store_id' => $this->store,
    ]);
    $second_order->save();
    $this->secondOrder = $this->reloadEntity($second_order);
  }

  /**
   * @covers ::requestUpdate
   * @covers ::needsUpdate
   * @covers ::updateOrders
   * @covers ::updateOrder
   */
  public function testUpdate() {
    $this->assertTrue($this->firstOrder->getTotalPaid()->isZero());
    $this->assertTrue($this->secondOrder->getTotalPaid()->isZero());

    $this->assertFalse($this->paymentOrderUpdater->needsUpdate($this->firstOrder));
    $this->assertFalse($this->paymentOrderUpdater->needsUpdate($this->secondOrder));
    $this->paymentOrderUpdater->requestUpdate($this->firstOrder);
    $this->assertTrue($this->paymentOrderUpdater->needsUpdate($this->firstOrder));
    $this->assertFalse($this->paymentOrderUpdater->needsUpdate($this->secondOrder));

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $first_payment */
    $first_payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'onsite',
      'order_id' => $this->firstOrder->id(),
      'amount' => new Price('10', 'USD'),
      'state' => 'completed',
    ]);
    $first_payment->save();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $second_payment */
    $second_payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'onsite',
      'order_id' => $this->firstOrder->id(),
      'amount' => new Price('10', 'USD'),
      'state' => 'authorization',
    ]);
    $second_payment->save();

    $this->paymentOrderUpdater->updateOrders();
    $this->firstOrder = $this->reloadEntity($this->firstOrder);
    $this->secondOrder = $this->reloadEntity($this->secondOrder);

    // Confirm that only the first payment was counted, since the second one
    // hasn't been completed yet.
    $this->assertEquals($first_payment->getAmount(), $this->firstOrder->getTotalPaid());
    $this->assertTrue($this->secondOrder->getTotalPaid()->isZero());

    // Confirm that the order is not resaved if total_paid hasn't changed.
    $changed = $this->firstOrder->getChangedTime();
    sleep(1);
    $this->paymentOrderUpdater->requestUpdate($this->firstOrder);
    $this->paymentOrderUpdater->updateOrders();
    $this->firstOrder = $this->reloadEntity($this->firstOrder);
    $this->assertEquals($changed, $this->firstOrder->getChangedTime());
  }

}
