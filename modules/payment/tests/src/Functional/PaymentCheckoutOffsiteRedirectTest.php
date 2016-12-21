<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the integration between off-site payments and checkout.
 *
 * @group commerce
 */
class PaymentCheckoutOffsiteRedirectTest extends CommerceBrowserTestBase {

  use StoreCreationTrait;

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
  protected function setUp() {
    parent::setUp();

    $store = $this->createStore('Demo', 'demo@example.com', 'default', TRUE);

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '29.99',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$store],
    ]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'example_offsite_redirect',
      'label' => 'Example',
      'plugin' => 'example_offsite_redirect',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'redirect_method' => 'post',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();

    // Cheat so we don't need JS to interact w/ Address field widget.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $customer_form_display */
    $customer_form_display = EntityFormDisplay::load('profile.customer.default');
    $address_component = $customer_form_display->getComponent('address');
    $address_component['settings']['default_country'] = 'US';
    $customer_form_display->setComponent('address', $address_component);
    $customer_form_display->save();

  }

  /**
   * Tests the off-site redirect using the POST redirect method.
   */
  public function testCheckoutWithOffsiteRedirectPost() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([
      'payment_information[address][0][given_name]' => 'Johnny',
      'payment_information[address][0][family_name]' => 'Appleseed',
      'payment_information[address][0][address_line1]' => '123 New York Drive',
      'payment_information[address][0][locality]' => 'New York City',
      'payment_information[address][0][administrative_area]' => 'NY',
      'payment_information[address][0][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains($this->loggedInUser->getEmail());
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Pay and complete purchase');
    // No JS so we need to manually click the button to submit payment.
    $this->submitForm([], 'Proceed to Example');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $order = Order::load(1);
    $payment_gateway = $order->payment_gateway->entity;
    $this->assertEquals('example_offsite_redirect', $payment_gateway->id());

    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
  }

  /**
   * Tests the off-site redirect using the GET redirect method.
   */
  public function testCheckoutWithOffsiteRedirectGet() {
    $payment_gateway = PaymentGateway::load('example_offsite_redirect');
    $payment_gateway->getPlugin()->setConfiguration([
      'redirect_method' => 'get',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([
      'payment_information[address][0][given_name]' => 'Johnny',
      'payment_information[address][0][family_name]' => 'Appleseed',
      'payment_information[address][0][address_line1]' => '123 New York Drive',
      'payment_information[address][0][locality]' => 'New York City',
      'payment_information[address][0][administrative_area]' => 'NY',
      'payment_information[address][0][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains($this->loggedInUser->getEmail());
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $order = Order::load(1);
    $payment_gateway = $order->payment_gateway->entity;
    $this->assertEquals('example_offsite_redirect', $payment_gateway->id());
    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());
  }

}
