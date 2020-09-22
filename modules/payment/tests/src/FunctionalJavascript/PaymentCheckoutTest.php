<?php

namespace Drupal\Tests\commerce_payment\FunctionalJavascript;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the integration between payments and checkout.
 *
 * @group commerce
 */
class PaymentCheckoutTest extends CommerceWebDriverTestBase {

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
   * The default profile's address.
   *
   * @var array
   */
  protected $defaultAddress = [
    'country_code' => 'US',
    'administrative_area' => 'SC',
    'locality' => 'Greenville',
    'postal_code' => '29616',
    'address_line1' => '9 Drupal Ave',
    'given_name' => 'Bryan',
    'family_name' => 'Centarro',
  ];

  /**
   * The default profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $defaultProfile;

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

    $this->store->set('billing_countries', ['FR', 'US']);
    $this->store->save();

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

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $skipped_gateway */
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

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'stored_offsite',
      'label' => 'Stored off-site',
      'plugin' => 'example_stored_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
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
    $payment_gateway->save();

    $this->defaultProfile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->defaultAddress,
    ]);
    $profile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => 0,
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
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
      'Credit card',
      'Example',
    ];
    $page = $this->getSession()->getPage();
    foreach ($expected_options as $expected_option) {
      $radio_button = $page->findField($expected_option);
      $this->assertNotNull($radio_button);
    }
    $default_radio_button = $page->findField('Visa ending in 9999');
    $this->assertNotEmpty($default_radio_button->getAttribute('checked'));

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite2',
      'label' => 'On-site 2',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->setPluginConfiguration([
      'api_key' => '2342fewfsfs',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $first_onsite_gateway = PaymentGateway::load('onsite');
    $first_onsite_gateway->setStatus(FALSE);
    $first_onsite_gateway->save();
    $second_onsite_gateway = PaymentGateway::load('onsite2');
    $second_onsite_gateway->setStatus(FALSE);
    $second_onsite_gateway->save();
    $manual_gateway = PaymentGateway::load('manual');
    $manual_gateway->setStatus(FALSE);
    $manual_gateway->save();
    $stored_offsite_gateway = PaymentGateway::load('stored_offsite');
    $stored_offsite_gateway->setStatus(FALSE);
    $stored_offsite_gateway->save();

    // A single radio button should be selected and hidden.
    $this->drupalGet('checkout/1');
    $radio_button = $page->findField('Example');
    $this->assertNull($radio_button);
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');
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
    $radio_button = $this->getSession()->getPage()->findField('Credit card');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[add_payment_method][billing_information]');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4012888888881881',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2024',
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
    $this->assertSession()->pageTextContains('Expires 2/2024');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertFalse($order->isLocked());
    $this->assertEquals('onsite', $order->get('payment_gateway')->target_id);
    /** @var \Drupal\profile\Entity\ProfileInterface $order_billing_profile */
    $order_billing_profile = $order->getBillingProfile();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    $this->assertEquals('1881', $payment_method->get('card_number')->value);
    // Confirm that the billing profile has the expected address.
    $expected_address = [
      'given_name' => 'Johnny',
      'family_name' => 'Appleseed',
      'address_line1' => '123 New York Drive',
      'locality' => 'New York City',
      'administrative_area' => 'NY',
      'postal_code' => '10001',
      'country_code' => 'US',
    ];
    $payment_method_profile = $payment_method->getBillingProfile();
    $this->assertEquals($expected_address, array_filter($payment_method_profile->get('address')->first()->toArray()));
    $this->assertNotEmpty($payment_method_profile->getData('address_book_profile_id'));
    $this->assertEmpty($payment_method_profile->getData('copy_to_address_book'));
    // Verify that the billing information was copied to the order.
    $this->assertEquals($expected_address, array_filter($order_billing_profile->get('address')->first()->toArray()));
    $this->assertNotEquals($order_billing_profile->id(), $payment_method_profile->id());
    $this->assertNotEmpty($order_billing_profile->getData('address_book_profile_id'));
    $this->assertEmpty($order_billing_profile->getData('copy_to_address_book'));
    // Confirm that the address book profile was updated.
    $this->defaultProfile = $this->reloadEntity($this->defaultProfile);
    $this->assertEquals($expected_address, array_filter($this->defaultProfile->get('address')->first()->toArray()));
    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    $this->assertEquals('authorization', $payment->getState()->getId());
    $this->assertEquals('A', $payment->getAvsResponseCode());
    $this->assertEquals('Address', $payment->getAvsResponseCodeLabel());
  }

  /**
   * Tests checkout with an existing payment method.
   */
  public function testCheckoutWithExistingPaymentMethod() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    // Make the order partially paid, to confirm that checkout only charges
    // for the remaining amount.
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'onsite',
      'order_id' => '1',
      'amount' => new Price('20', 'USD'),
      'state' => 'completed',
    ]);
    $payment->save();
    $order = Order::load(1);
    // Save the order to recalculate the balance.
    $order->save();
    $this->assertEquals(new Price('20', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('19.99', 'USD'), $order->getBalance());

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

    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache([1]);
    $order = Order::load(1);
    $this->assertEquals('onsite', $order->get('payment_gateway')->target_id);
    $this->assertEquals('1', $order->get('payment_method')->target_id);
    $this->assertFalse($order->isLocked());
    // Verify that a completed payment was made.
    $payment = Payment::load(2);
    $this->assertNotNull($payment);
    $this->assertEquals('completed', $payment->getState()->getId());
    $this->assertEquals(new Price('19.99', 'USD'), $payment->getAmount());
    $this->assertEquals(new Price('39.99', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('0', 'USD'), $order->getBalance());
    $this->assertEquals('A', $payment->getAvsResponseCode());
    $this->assertEquals('Address', $payment->getAvsResponseCodeLabel());

    /** @var \Drupal\profile\Entity\ProfileInterface $order_billing_profile */
    $order_billing_profile = $order->getBillingProfile();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    /** @var \Drupal\profile\Entity\ProfileInterface $payment_method_profile */
    $payment_method_profile = $payment_method->getBillingProfile();
    // Verify that the billing information was copied to the order.
    $this->assertTrue($order_billing_profile->equalToProfile($payment_method_profile));
    $this->assertNotEquals($order_billing_profile->id(), $payment_method_profile->id());
  }

  /**
   * Tests that a declined payment does not complete checkout.
   */
  public function testCheckoutWithDeclinedPaymentMethod() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Credit card');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[add_payment_method][billing_information]');
    $this->getSession()->getPage()->fillField('payment_information[add_payment_method][billing_information][select_address]', '_new');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4111111111111111',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2024',
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
    $this->assertSession()->pageTextContains('Expires 2/2024');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextNotContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('We encountered an error processing your payment method. Please verify your details and try again.');
    $this->assertSession()->addressEquals('checkout/1/order_information');

    $order = Order::load(1);
    $this->assertFalse($order->isLocked());
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    $this->assertEquals('123 New York Drive', $payment_method->getBillingProfile()->get('address')->address_line1);
    // Confirm that the address book profile was not updated.
    $this->defaultProfile = $this->reloadEntity($this->defaultProfile);
    $this->assertEquals('9 Drupal Ave', $this->defaultProfile->get('address')->address_line1);
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Bryan Centarro');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertFalse($order->isLocked());
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);
    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $order->getBillingProfile();
    $this->assertEquals('9 Drupal Ave', $billing_profile->get('address')->address_line1);
    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
  }

  /**
   * Tests checkout with an off-site gateway (POST redirect method, manual).
   *
   * In this scenario the customer must click the submit button on the payment
   * page in order to proceed to the gateway.
   */
  public function testCheckoutWithOffsiteRedirectPostManual() {
    $payment_gateway = PaymentGateway::load('offsite');
    $payment_gateway->setPluginConfiguration([
      'redirect_method' => 'post_manual',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Bryan Centarro');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');

    $this->assertSession()->addressEquals('checkout/1/payment');
    $order = Order::load(1);
    $this->assertTrue($order->isLocked());
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);

    $this->submitForm([], 'Proceed to Example');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache(['1']);
    $order = Order::load(1);
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);
    $this->assertFalse($order->isLocked());
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
    $offiste_stored_gateway = PaymentGateway::load('stored_offsite');
    $offiste_stored_gateway->setStatus(FALSE);
    $offiste_stored_gateway->save();

    $payment_gateway = PaymentGateway::load('offsite');
    $payment_gateway->setPluginConfiguration([
      'redirect_method' => 'get',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Bryan Centarro');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);
    $this->assertFalse($order->isLocked());
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
    $payment_gateway->setPluginConfiguration([
      'redirect_method' => 'get',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'payment_information[billing_information][address][0][address][family_name]' => 'FAIL',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Bryan FAIL');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextNotContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('We encountered an unexpected error processing your payment. Please try again later.');
    $this->assertSession()->addressEquals('checkout/1/order_information');

    $order = Order::load(1);
    $this->assertFalse($order->isLocked());
    // Verify a payment was not created.
    $payment = Payment::load(1);
    $this->assertNull($payment);
  }

  /**
   * Tests checkout with an off-site gateway that supports notifications.
   *
   * We simulate onNotify() being called before onReturn(), resulting in the
   * order being fully paid and placed before the customer returns to the site.
   */
  public function testCheckoutWithOffsitePaymentNotify() {
    $payment_gateway = PaymentGateway::load('offsite');
    $payment_gateway->setPluginConfiguration([
      'redirect_method' => 'post_manual',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Bryan Centarro');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');

    $this->assertSession()->addressEquals('checkout/1/payment');
    // Simulate the order being paid in full.
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => 'offsite',
      'order_id' => '1',
      'amount' => new Price('39.99', 'USD'),
      'state' => 'completed',
    ]);
    $payment->save();
    $order = Order::load(1);
    // Save the order to recalculate the balance.
    $order->save();
    $this->assertTrue($order->isPaid());
    $this->assertFalse($order->isLocked());

    // Go to the return url and confirm that it works.
    $this->drupalGet('checkout/1/payment/return');
    $this->assertSession()->addressEquals('checkout/1/complete');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    // Confirm that only one payment was made.
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertCount(1, $payments);
  }

  /**
   * Tests checkout with a stored off-site gateway (POST redirect method).
   */
  public function testCheckoutWithStoredOffsiteRedirectPost() {
    // Remove the initial test payment methods.
    PaymentMethod::load(1)->delete();
    PaymentMethod::load(2)->delete();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $this->assertSession()->fieldNotExists('Visa ending in 1111');
    $radio_button = $this->getSession()->getPage()->findField('Credit card (Example Stored Offsite)');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');

    $this->assertSession()->pageTextContains('Payment information');
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Bryan Centarro');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertFalse($order->isLocked());
    $this->assertEquals('stored_offsite', $order->get('payment_gateway')->target_id);
    // @todo PaymentUpdater should sync this from the payment. https://www.drupal.org/project/commerce/issues/3137636
    // $this->assertEquals(3, $order->get('payment_method')->target_id);
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    // Verify that a reusable payment method was created.
    $payment_method = $payment->getPaymentMethod();
    $this->assertEquals(TRUE, $payment_method->isReusable());
    $this->assertEquals('stored_offsite', $payment_method->getPaymentGatewayId());
    $this->assertEquals(3, $payment_method->id());

    // Assert that the created payment method can be loaded.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->createScreenshot('../checkout_1.png');
    $this->submitForm([], 'Add to cart');
    $this->createScreenshot('../checkout_2.png');
    $this->drupalGet('checkout/2');
    $this->createScreenshot('../checkout_3.png');
    $radio_button = $this->getSession()->getPage()->findField('Visa ending in 1111');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Continue to review');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 2. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertFalse($order->isLocked());
    $this->assertEquals('stored_offsite', $order->get('payment_gateway')->target_id);
    // @todo PaymentUpdater should sync this from the payment. https://www.drupal.org/project/commerce/issues/3137636
    // $this->assertEquals(3, $order->get('payment_method')->target_id);
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
    $this->assertEquals('Z', $payment->getAvsResponseCode());
    $this->assertEquals('ZIP', $payment->getAvsResponseCodeLabel());
    $this->assertEquals(3, $payment->get('payment_method')->target_id);
  }

  /**
   * Tests checkout with a manual gateway.
   */
  public function testManual() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    // Make the order partially paid, to confirm that checkout only charges
    // for the remaining amount.
    $payment = Payment::create([
      'type' => 'payment_manual',
      'payment_gateway' => 'manual',
      'order_id' => '1',
      'amount' => new Price('20', 'USD'),
      'state' => 'completed',
    ]);
    $payment->save();
    $order = Order::load(1);
    // Save the order to recalculate the balance.
    $order->save();
    $this->assertEquals(new Price('20', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('19.99', 'USD'), $order->getBalance());

    $radio_button = $this->getSession()->getPage()->findField('Cash on delivery');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Cash on delivery');
    $this->assertSession()->pageTextContains('Bryan Centarro');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('Sample payment instructions.');

    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache([1]);
    $order = Order::load(1);
    $this->assertEquals('manual', $order->get('payment_gateway')->target_id);
    $this->assertFalse($order->isLocked());
    // Verify that a pending payment was created, and that the totals are
    // still unchanged.
    $payment = Payment::load(2);
    $this->assertNotNull($payment);
    $this->assertEquals('pending', $payment->getState()->getId());
    $this->assertEquals(new Price('19.99', 'USD'), $payment->getAmount());
    $this->assertEquals(new Price('20', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('19.99', 'USD'), $order->getBalance());
  }

  /**
   * Tests checkout with a manual gateway, without billing information.
   */
  public function testManualWithoutBilling() {
    $payment_gateway = PaymentGateway::load('manual');
    $payment_gateway->setPluginConfiguration([
      'collect_billing_information' => FALSE,
      'display_label' => 'Cash on delivery',
      'instructions' => [
        'value' => 'You will pay for order #[commerce_order:order_id] in [commerce_payment:amount:currency_code].',
        'format' => 'plain_text',
      ],
    ]);
    $payment_gateway->save();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');
    $radio_button = $this->getSession()->getPage()->findField('Cash on delivery');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('Country');
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Cash on delivery');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    // Confirm token replacement works.
    $this->assertSession()->pageTextContains('You will pay for order #1 in USD.');

    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache([1]);
    $order = Order::load(1);
    $this->assertEquals('manual', $order->get('payment_gateway')->target_id);
    $this->assertNull($order->getBillingProfile());
    $this->assertFalse($order->isLocked());
  }

  /**
   * Tests a free order, where only the billing information is collected.
   */
  public function testFreeOrder() {
    // Prepare a different address book profile to test switching.
    $new_address = [
      'given_name' => 'Johnny',
      'family_name' => 'Appleseed',
      'address_line1' => '123 New York Drive',
      'locality' => 'New York City',
      'administrative_area' => 'NY',
      'postal_code' => '10001',
      'country_code' => 'US',
    ];
    $new_address_book_profile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $new_address,
    ]);

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Add an adjustment to zero out the order total.
    $order = Order::load(1);
    $order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Surprise, it is free!',
      'amount' => $order->getTotalPrice()->multiply('-1'),
      'locked' => TRUE,
    ]));
    $order->save();

    $this->drupalGet('checkout/1');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextNotContains('Payment information');
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');
    $this->getSession()->getPage()->fillField('payment_information[billing_information][select_address]', $new_address_book_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($new_address, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextNotContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');

    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
  }

  /**
   * Tests a paid order, where only the billing information is collected.
   */
  public function testPaidOrder() {
    // Prepare a different address book profile to test switching.
    $new_address = [
      'given_name' => 'Johnny',
      'family_name' => 'Appleseed',
      'address_line1' => '123 New York Drive',
      'locality' => 'New York City',
      'administrative_area' => 'NY',
      'postal_code' => '10001',
      'country_code' => 'US',
    ];
    $new_address_book_profile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $new_address,
    ]);

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    $order = Order::load(1);
    $order->setTotalPaid($order->getTotalPrice());
    $order->save();

    $this->drupalGet('checkout/1');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextNotContains('Payment information');
    $this->assertRenderedAddress($this->defaultAddress, 'payment_information[billing_information]');
    $this->getSession()->getPage()->fillField('payment_information[billing_information][select_address]', $new_address_book_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($new_address, 'payment_information[billing_information]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextNotContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');

    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
  }

}
