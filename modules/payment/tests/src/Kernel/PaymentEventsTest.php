<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the payment events.
 *
 * @group commerce
 */
class PaymentEventsTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'payment_events_test',
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
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();

    $user = $this->createUser();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method_active = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      // Thu, 16 Jan 2020.
      'expires' => '1579132800',
      'uid' => $user->id(),
    ]);
    $payment_method_active->save();
  }

  /**
   * Tests the basic payment events.
   */
  public function testPaymentEvents() {
    // Create a dummy payment.
    $payment = Payment::create([
      'payment_gateway' => 'example',
      'payment_method' => 'credit_card',
      'remote_id' => '123456',
      'amount' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
      'state' => 'capture_completed',
      'test' => TRUE,
    ]);
    $payment->save();

    // Check the create event.
    $event_recorder = \Drupal::state()->get('payment_events_test.event', FALSE);
    $this->assertEquals('commerce_payment.commerce_payment.create', $event_recorder['event_name']);

    // Reload the payment.
    $this->reloadEntity($payment);

    // Check the load event.
    $event_recorder = \Drupal::state()->get('payment_events_test.event', FALSE);
    $this->assertEquals('commerce_payment.commerce_payment.load', $event_recorder['event_name']);
  }

}
