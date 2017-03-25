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
    $this->submitForm([], 'Continue as Guest');
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
    $this->assertSession()->pageTextNotContains('Order Summary');
    $this->submitForm([], 'Continue as Guest');
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
   * Tests the order summary.
   */
  public function testOrderSummary() {
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Test the default settings: ensure the default view is shown.
    $this->drupalGet('/checkout/1');
    $this->assertSession()->elementExists('css', '.view-id-commerce_checkout_order_summary');

    // Disable the order summary.
    $this->drupalGet('/admin/commerce/config/checkout-flows/manage/default');
    $this->submitForm(['configuration[order_summary_view]' => ''], t('Save'));
    $this->drupalGet('/checkout/1');
    $this->assertSession()->elementNotExists('css', '.view-id-commerce_checkout_order_summary');

    // Use a different view for the order summary.
    $this->drupalGet('/admin/structure/views/view/commerce_checkout_order_summary/duplicate');
    $this->submitForm(['id' => 'duplicate_of_commerce_checkout_order_summary'], 'Duplicate');
    $this->drupalGet('/admin/commerce/config/checkout-flows/manage/default');
    $this->submitForm(['configuration[order_summary_view]' => 'duplicate_of_commerce_checkout_order_summary'], t('Save'));
    $this->drupalGet('/checkout/1');
    $this->assertSession()->elementExists('css', '.view-id-duplicate_of_commerce_checkout_order_summary');
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

}
