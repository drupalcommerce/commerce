<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the checkout of an order.
 *
 * @group commerce
 */
class CheckoutOrderTest extends CommerceBrowserTestBase {

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
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_checkout_flow',
      'administer views',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
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
  }

  /**
   * Tests checkout flow cache metadata.
   */
  public function testCacheMetadata() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('1 item');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextNotContains('Order Summary');
    $this->assertCheckoutProgressStep('Login');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->container->get('entity_type.manager')->getStorage('commerce_order')->load(1);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->container->get('entity_type.manager')->getStorage('commerce_checkout_flow')->load('default');

    // We're on a form, so no Page Cache.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', NULL);
    // Dynamic page cache should be present, and a MISS.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');

    // Assert cache tags bubbled.
    $cache_tags_header = $this->getSession()->getResponseHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(strpos($cache_tags_header, 'commerce_order:' . $order->id()) !== FALSE);
    foreach ($order->getItems() as $item) {
      $this->assertTrue(strpos($cache_tags_header, 'commerce_order_item:' . $item->id()) !== FALSE);
    }
    foreach ($checkout_flow->getCacheTags() as $cache_tag) {
      $this->assertTrue(strpos($cache_tags_header, $cache_tag) !== FALSE);
    }

    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    // Saving the order should bust the cache.
    $this->container->get('commerce_order.order_refresh')->refresh($order);
    $order->save();

    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    // Saving the checkout flow configuration entity should bust the cache.
    $checkout_flow->save();

    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');
  }

  /**
   * Tests than an order can go through checkout steps.
   */
  public function testGuestOrderCheckout() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('1 item');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextNotContains('Order Summary');

    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');
    // Test second order.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('1 item');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertCheckoutProgressStep('Login');
    $this->assertSession()->pageTextNotContains('Order Summary');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->assertCheckoutProgressStep('Review');

    // Go back and forth.
    $this->getSession()->getPage()->clickLink('Go back');
    $this->assertCheckoutProgressStep('Order information');
    $this->getSession()->getPage()->pressButton('Continue to review');
    $this->assertCheckoutProgressStep('Review');

    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 2. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');
  }

  /**
   * Tests that you can register from the checkout pane.
   */
  public function testRegisterOrderCheckout() {
    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.login.allow_guest_checkout', FALSE);
    $config->set('configuration.panes.login.allow_registration', TRUE);
    $config->save();

    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('New Customer');
    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Billing information');

    // Test account validation.
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('New Customer');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => '',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Email field is required.');

    $this->submitForm([
      'login[register][name]' => '',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Username field is required.');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => '',
      'login[register][password][pass2]' => '',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Password field is required.');

    $this->submitForm([
      'login[register][name]' => 'User name double email',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('The email address guest@example.com is already taken.');

    $this->submitForm([
      'login[register][name]' => 'User @#.``^ ù % name invalid',
      'login[register][mail]' => 'guest2@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('The username contains an illegal character.');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest2@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('The username User name is already taken.');
  }

  /**
   * Tests checkout behaviour after a cart update.
   */
  public function testCheckoutFlowOnCartUpdate() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->getSession()->getPage()->findLink('your cart')->click();
    // Submit the form until review.
    $this->submitForm([], 'Checkout');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Order information');
    $this->assertSession()->elementNotContains('css', 'h1.page-title', 'Review');
    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Review');
    // By default the checkout step is preserved upon return.
    $this->drupalGet('/checkout/1');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Review');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product2 = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
    // Adding a new product to the cart resets the checkout step.
    $this->drupalGet($product2->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->getSession()->getPage()->findLink('your cart')->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Order information');
    $this->assertSession()->elementNotContains('css', 'h1.page-title', 'Review');

    // Removing a product from the cart resets the checkout step.
    $this->submitForm([], 'Continue to review');
    $this->drupalGet('/cart');
    $this->submitForm([], 'Remove');
    $this->submitForm([], 'Checkout');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Order information');
    $this->assertSession()->elementNotContains('css', 'h1.page-title', 'Review');
  }

  /**
   * Asserts the current step in the checkout progress block.
   *
   * @param string $expected
   *   The expected value.
   */
  protected function assertCheckoutProgressStep($expected) {
    $current_step = $this->getSession()->getPage()->find('css', '.checkout-progress--step__current')->getText();
    $this->assertEquals($expected, $current_step);
  }

}
