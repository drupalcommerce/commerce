<?php

namespace Drupal\Tests\commerce_payment\FunctionalJavascript;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the integration between payments and checkout.
 *
 * @group commerce
 */
class PaymentCheckoutTest extends CommerceBrowserTestBase {

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
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer profile',
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
    $skipped_gateway = PaymentGateway::create([
      'id' => 'onsite_skipped',
      'label' => 'On-site Skipped',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
      'conditions' => [
        [
          'plugin' => 'order_total_price',
          'configuration' => [
            'operator' => '<',
            'amount' => [
              'number' => '1.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $skipped_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'manual',
      'label' => 'Manual',
      'plugin' => 'manual',
      'configuration' => [
        'display_label' => 'Cash on delivery',
        'instructions' => [
          'value' => 'Sample payment instructions.',
          'format' => 'plain_text',
        ],
      ],
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

    $this->orderPaymentMethod = $this->createEntity('commerce_payment_method', [
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '9999',
      'reusable' => FALSE,
    ]);
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
      'Visa ending in 1111',
      'Visa ending in 9999',
      'New credit card',
      'Example',
    ];
    $page = $this->getSession()->getPage();
    foreach ($expected_options as $expected_option) {
      $radio_button = $page->findField($expected_option);
      $this->assertNotNull($radio_button);
    }
    $default_radio_button = $page->findField('Visa ending in 9999');
    $this->assertTrue($default_radio_button->getAttribute('checked'));

    // The order's payment gateway should be selected if it is not on-site,
    // and there is no order payment method.
    $order = Order::load(1);
    $order->payment_method = NULL;
    $order->payment_gateway = 'offsite';
    $order->save();
    $this->drupalGet('checkout/1');
    $this->assertSession()->pageTextContains('Payment information');
    $default_radio_button = $page->findField('Example');
    $this->assertTrue($default_radio_button->getAttribute('checked'));

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'onsite2',
      'label' => 'On-site 2',
      'plugin' => 'example_onsite',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'api_key' => '2342fewfsfs',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();

    $first_onsite_gateway = PaymentGateway::load('onsite');
    $first_onsite_gateway->setStatus(FALSE);
    $first_onsite_gateway->save();
    $second_onsite_gateway = PaymentGateway::load('onsite2');
    $second_onsite_gateway->setStatus(FALSE);
    $second_onsite_gateway->save();
    $manual_gateway = PaymentGateway::load('manual');
    $manual_gateway->setStatus(FALSE);
    $manual_gateway->save();

    // A single radio button should be selected and hidden.
    $this->drupalGet('checkout/1');
    $radio_button = $page->findField('Example');
    $this->assertNull($radio_button);
    $this->assertSession()->fieldExists('payment_information[billing_information][address][0][address][postal_code]');
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
    $this->assertSession()->pageTextContains('Visa ending in 1111');
    $this->assertSession()->pageTextContains('Expires 3/2028');
    $this->assertSession()->pageTextContains('Frederick Pabst');
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertEquals('onsite', $order->get('payment_gateway')->target_id);
    $this->assertEquals('1', $order->get('payment_method')->target_id);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    $this->assertEquals('completed', $payment->getState()->value);
  }

  /**
   * Tests checkout with a new payment method.
   */
  public function testCheckoutWithNewPaymentMethod() {
    // Test the 'capture' setting of PaymentProcess while here.
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlow $checkout_flow */
    $checkout_flow = CheckoutFlow::load('default');
    $plugin = $checkout_flow->getPlugin();
    $configuration = $plugin->getConfiguration();
    $configuration['panes']['payment_process']['capture'] = FALSE;
    $plugin->setConfiguration($configuration);
    $checkout_flow->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('New credit card');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4012888888881881',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2020',
      'payment_information[add_payment_method][payment_details][security_code]' => '123',
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Visa ending in 1881');
    $this->assertSession()->pageTextContains('Expires 2/2020');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertEquals('onsite', $order->get('payment_gateway')->target_id);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    $this->assertEquals('1881', $payment_method->get('card_number')->value);
    $this->assertEquals('123 New York Drive', $payment_method->getBillingProfile()->get('address')->address_line1);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    $this->assertEquals('authorization', $payment->getState()->value);
  }

  /**
   * Tests that a declined payment does not complete checkout.
   */
  public function testCheckoutWithDeclinedPaymentMethod() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('New credit card');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4111111111111111',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2020',
      'payment_information[add_payment_method][payment_details][security_code]' => '123',
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'Somewhere',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'WI',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '53140',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Visa ending in 1111');
    $this->assertSession()->pageTextContains('Expires 2/2020');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextNotContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('We encountered an error processing your payment method. Please verify your details and try again.');
    $this->assertSession()->addressEquals('checkout/1/order_information');

    // Verify a payment was not created.
    $payment = Payment::load(1);
    $this->assertNull($payment);
  }

  /**
   * Tests checkout with an off-site gateway (POST redirect method).
   */
  public function testCheckoutWithOffsiteRedirectPost() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $order = Order::load(1);
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
  }

  /**
   * Tests checkout with an off-site gateway (GET redirect method).
   */
  public function testCheckoutWithOffsiteRedirectGet() {
    // Checkout must work when the off-site gateway is alone, and the
    // radio button hidden.
    $onsite_gateway = PaymentGateway::load('onsite');
    $onsite_gateway->setStatus(FALSE);
    $onsite_gateway->save();
    $manual_gateway = PaymentGateway::load('manual');
    $manual_gateway->setStatus(FALSE);
    $manual_gateway->save();

    $payment_gateway = PaymentGateway::load('offsite');
    $payment_gateway->getPlugin()->setConfiguration([
      'redirect_method' => 'get',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $order = Order::load(1);
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
  }

  /**
   * Tests checkout with an off-site gateway (GET redirect method) that fails.
   *
   * The off-site form throws an exception, simulating an API fail.
   */
  public function testFailedCheckoutWithOffsiteRedirectGet() {
    $payment_gateway = PaymentGateway::load('offsite');
    $payment_gateway->getPlugin()->setConfiguration([
      'redirect_method' => 'get',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'FAIL',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny FAIL');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextNotContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('We encountered an unexpected error processing your payment. Please try again later.');
    $this->assertSession()->addressEquals('checkout/1/order_information');

    // Verify a payment was not created.
    $payment = Payment::load(1);
    $this->assertNull($payment);
  }

  /**
   * Tests checkout with a manual gateway.
   */
  public function testCheckoutWithManual() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Cash on delivery');
    $radio_button->click();
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('Sample payment instructions.');
    $order = Order::load(1);
    $this->assertEquals('manual', $order->get('payment_gateway')->target_id);

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
  }

}
