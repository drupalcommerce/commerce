<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the admin UI for payments of type 'payment_default'.
 *
 * @group commerce
 */
class DefaultPaymentAdminTest extends CommerceBrowserTestBase {

  /**
   * An on-site payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * Admin's payment method.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface
   */
  protected $paymentMethod;

  /**
   * The base admin payment uri.
   *
   * @var string
   */
  protected $paymentUri;

  /**
   * The admin's order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_product',
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer commerce_payment_gateway',
      'administer commerce_payment',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $profile = $this->createEntity('profile', [
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
      'uid' => $this->adminUser->id(),
    ]);

    $this->paymentGateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $this->paymentMethod = $this->createEntity('commerce_payment_method', [
      'uid' => $this->loggedInUser->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'billing_profile' => $profile,
    ]);

    $details = [
      'type' => 'visa',
      'number' => '4111111111111111',
      'expiration' => ['month' => '01', 'year' => date('Y') + 1],
    ];
    $this->paymentGateway->getPlugin()->createPaymentMethod($this->paymentMethod, $details);

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);

    $this->order = $this->createEntity('commerce_order', [
      'uid' => $this->loggedInUser->id(),
      'type' => 'default',
      'state' => 'draft',
      'order_items' => [$order_item],
      'store_id' => $this->store,
    ]);

    $this->paymentUri = Url::fromRoute('entity.commerce_payment.collection', [
      'commerce_order' => $this->order->id(),
    ])->toString();
  }

  /**
   * Tests that a Payments tab is visible on the order page.
   */
  public function testPaymentTab() {
    $this->drupalGet($this->order->toUrl());
    $this->assertSession()->linkExists('Payments');
    $this->assertSession()->linkByHrefExists($this->paymentUri);
  }

  /**
   * Tests creating a payment for an order.
   */
  public function testPaymentCreation() {
    $this->drupalGet($this->paymentUri);
    $this->getSession()->getPage()->clickLink('Add payment');
    $this->assertSession()->addressEquals($this->paymentUri . '/add');
    $this->assertSession()->pageTextContains('Visa ending in 1111');
    $this->assertSession()->checkboxChecked('payment_method');

    $this->getSession()->getPage()->pressButton('Continue');
    $this->submitForm(['payment[amount][number]' => '100'], 'Add payment');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Completed');

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::load(1);
    $this->assertEquals($payment->getOrderId(), $this->order->id());
    $this->assertEquals($payment->getAmount()->getNumber(), '100');
    $this->assertNotEmpty($payment->getCompletedTime());
  }

  /**
   * Tests capturing a payment after creation.
   */
  public function testPaymentCapture() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment, FALSE);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Authorization');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/capture');
    $this->submitForm(['payment[amount][number]' => '10'], 'Capture');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Authorization');
    $this->assertSession()->pageTextContains('Completed');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Completed');
  }

  /**
   * Tests refunding a payment after capturing.
   */
  public function testPaymentRefund() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment, TRUE);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Completed');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/refund');
    $this->submitForm(['payment[amount][number]' => '10'], 'Refund');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Completed');
    $this->assertSession()->pageTextContains('Refunded');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Refunded');
  }

  /**
   * Tests voiding a payment after creation.
   */
  public function testPaymentVoid() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment, FALSE);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Authorization');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/void');
    $this->getSession()->getPage()->pressButton('Void');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Authorization (Voided)');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Authorization (Voided)');
  }

  /**
   * Tests deleting a payment after creation.
   */
  public function testPaymentDelete() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment, FALSE);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Authorization');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Authorization');

    $payment = Payment::load($payment->id());
    $this->assertNull($payment);
  }

}
