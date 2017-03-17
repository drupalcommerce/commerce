<?php

namespace Drupal\Tests\commerce_payment\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the integration between payments and checkout.
 *
 * @group commerce
 */
class ManualPaymentCheckoutTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * A non-reusable order payment method.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface
   */
  protected $orderPaymentMethod;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer profiles',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'manual',
      'label' => 'Manual',
      'plugin' => 'manual',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'reusable' => '1',
      'expires' => '',
      'instructions' => [
        'value' => 'Test instructions.',
        'format' => 'plain_text',
      ],
      'payment_method_types' => ['manual'],
    ]);
    $gateway->save();

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
    $payment_method = $this->createEntity('commerce_payment_method', [
      'uid' => $this->adminUser->id(),
      'type' => 'manual',
      'payment_gateway' => 'manual',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->save();
  }

  /**
   * Tests the structure of the PaymentInformation checkout pane.
   */
  public function testPaymentInformation() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    // The order's payment method must always be available in the pane.
    $order = Order::load(1);
    $order->payment_method = $this->orderPaymentMethod;
    $order->save();
    $this->drupalGet('checkout/1');
    $this->assertSession()->pageTextContains('Payment information');

    $expected_options = [
      'Manual for Frederick Pabst (Pabst Blue Ribbon Dr, Milwaukee)',
      'New manual',
    ];
    $page = $this->getSession()->getPage();
    foreach ($expected_options as $expected_option) {
      $radio_button = $page->findField($expected_option);
      $this->assertNotNull($radio_button);
    }
    $default_radio_button = $page->findField('Manual for Frederick Pabst (Pabst Blue Ribbon Dr, Milwaukee)');
    $this->assertTrue($default_radio_button->getAttribute('checked'));
  }

  /**
   * Tests checkout with an existing payment method.
   */
  public function testCheckoutWithExistingPaymentMethod() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    $this->submitForm([
      'payment_information[payment_method]' => '1',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Manual for Frederick Pabst (Pabst Blue Ribbon Dr, Milwaukee)');
    $this->assertSession()->pageTextContains('Expires 3/2028');
    $this->assertSession()->pageTextContains('Frederick Pabst');
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('Test instructions.');

    $order = Order::load(1);
    $this->assertEquals('manual', $order->get('payment_gateway')->target_id);
    $this->assertEquals('1', $order->get('payment_method')->target_id);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    $this->assertEquals('pending', $payment->getState()->value);
  }

  /**
   * Tests checkout with a new payment method.
   */
  public function testCheckoutWithNewPaymentMethod() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('New manual');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Manual for Johnny Appleseed (123 New York Drive, New York City)');
    $this->assertSession()->pageTextContains('Expires Never');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('Test instructions.');

    $order = Order::load(1);
    $this->assertEquals('manual', $order->get('payment_gateway')->target_id);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    $this->assertEquals('123 New York Drive', $payment_method->getBillingProfile()->get('address')->address_line1);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    $this->assertEquals('pending', $payment->getState()->value);
  }

  /**
   * Tests that a declined payment does not complete checkout.
   */
  public function testCheckoutWithDeclinedPaymentMethod() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('New manual');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'Somewhere',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'WI',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '53140',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Manual for Johnny Appleseed (123 New York Drive, Somewhere)');
    $this->assertSession()->pageTextContains('Expires Never');
    // Change the expires time so we can get decline for the payment method.
    $payment_method = PaymentMethod::load(2);
    $payment_method->setExpiresTime(strtotime('-1 day'))->save();
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextNotContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('We encountered an error processing your payment method. Please verify your details and try again.');
    $this->assertSession()->addressEquals('checkout/1/order_information');

    // Verify a payment was not created.
    $payment = Payment::load(1);
    $this->assertNull($payment);
  }

}
