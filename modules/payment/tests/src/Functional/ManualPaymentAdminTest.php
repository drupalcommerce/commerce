<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the admin payment UI for Manual payments.
 *
 * @group commerce
 */
class ManualPaymentAdminTest extends CommerceBrowserTestBase {

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
      'uid' => $this->loggedInUser->id(),
    ]);

    $this->paymentGateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'manual',
      'label' => 'Manual example',
      'plugin' => 'manual',
    ]);
    $this->paymentGateway->getPlugin()->setConfiguration([
      'reusable' => '1',
      'expires' => '',
      'instructions' => [
        'value' => 'Test instructions.',
        'format' => 'plain_text',
      ],
    ]);
    $this->paymentGateway->save();
    $this->paymentMethod = $this->createEntity('commerce_payment_method', [
      'uid' => $this->loggedInUser->id(),
      'type' => 'manual',
      'payment_gateway' => 'manual',
      'billing_profile' => $profile,
      'expires' => 0,
    ]);

    $details = ['manual' => ''];
    $this->paymentGateway->getPlugin()->createPaymentMethod($this->paymentMethod, $details);

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'test-product-01',
      'price' => new Price('10', 'USD'),
    ]);

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'quantity' => 1,
      'purchased_entity' => $variation,
      'unit_price' => new Price('10', 'USD'),
    ]);

    $this->order = $this->createEntity('commerce_order', [
      'uid' => $this->loggedInUser->id(),
      'type' => 'default',
      'state' => 'draft',
      'order_items' => [$order_item],
      'store_id' => $this->store,
    ]);

    $this->paymentUri = Url::fromRoute('entity.commerce_payment.collection', ['commerce_order' => $this->order->id()])->toString();
  }

  /**
   * Tests creating a payment for an order.
   */
  public function testPaymentCreation() {
    $this->drupalGet($this->paymentUri);
    $this->getSession()->getPage()->clickLink('Add payment');
    $this->assertSession()->addressEquals($this->paymentUri . '/add');
    $this->assertSession()->pageTextContains('Manual example for Frederick Pabst (Pabst Blue Ribbon Dr, Milwaukee)');

    $this->assertSession()->checkboxChecked('payment_method');

    $this->getSession()->getPage()->pressButton('Continue');
    $this->submitForm(['payment[amount][number]' => '100'], 'Add payment');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Pending');

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::load(1);
    $this->assertEquals($payment->getOrderId(), $this->order->id());
    $this->assertEquals($payment->getAmount()->getNumber(), '100');
  }

  /**
   * Tests completing a payment after creation.
   */
  public function testPaymentComplete() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Pending');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/complete');
    $this->submitForm(['payment[amount][number]' => '10'], 'Complete');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Pending');
    $this->assertSession()->pageTextContains('Completed');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Completed');
  }

  /**
   * Tests refunding a payment after completing.
   */
  public function testPaymentRefund() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment);
    $this->paymentGateway->getPlugin()->completePayment($payment, new Price('10', 'USD'));

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
   * Tests canceling a payment after creation.
   */
  public function testPaymentCancel() {
    $payment = $this->createEntity('commerce_payment', [
      'payment_gateway' => $this->paymentGateway->id(),
      'payment_method' => $this->paymentMethod->id(),
      'order_id' => $this->order->id(),
      'amount' => new Price('10', 'USD'),
    ]);

    $this->paymentGateway->getPlugin()->createPayment($payment);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Pending');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/operation/cancel');
    $this->getSession()->getPage()->pressButton('Cancel payment');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextContains('Canceled');

    $payment = Payment::load($payment->id());
    $this->assertEquals($payment->getState()->getLabel(), 'Canceled');
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

    $this->paymentGateway->getPlugin()->createPayment($payment);

    $this->drupalGet($this->paymentUri);
    $this->assertSession()->pageTextContains('Pending');

    $this->drupalGet($this->paymentUri . '/' . $payment->id() . '/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->addressEquals($this->paymentUri);
    $this->assertSession()->pageTextNotContains('Pending');

    $payment = Payment::load($payment->id());
    $this->assertNull($payment);
  }

}
