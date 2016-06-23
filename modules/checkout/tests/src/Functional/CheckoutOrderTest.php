<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the checkout of an order.
 *
 * @group niels
 */
class CheckoutOrderTest extends CommerceBrowserTestBase {

  use StoreCreationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface;
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
  public static $modules = ['system', 'field', 'user', 'text',
    'entity', 'views', 'address', 'profile', 'commerce', 'inline_entity_form',
    'commerce_price', 'commerce_store', 'commerce_product', 'commerce_cart',
    'commerce_checkout', 'commerce_order', 'views_ui',
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
        'amount' => 9.99,
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
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer checkout flows',
      'administer views',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests than an order can go through checkout steps.
   */
  public function testGuestOrderCheckout() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextNotContains('Order Summary');
    $this->submitForm([], 'Continue as Guest');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[address][0][recipient]' => $this->randomString(),
      'billing_information[address][0][organization]' => $this->randomString(),
      'billing_information[address][0][address_line1]' => $this->randomString(),
      'billing_information[address][0][locality]' => $this->randomString(),
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
  }

  /**
   * Test OrderSummary settings.
   */
  public function testOrderSummarySettings() {
    // Fill the cart.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Test OrderSummary default behavior.
    $this->drupalGet('/checkout/1');
    // Validate that the default OrderSummary-View is on the checkout-page.
    $this->assertSession()->elementExists('css', '.view-id-commerce_checkout_order_summary');

    // Disable OrderSummary.
    $this->drupalGet('/admin/commerce/config/checkout-flows/manage/default');
    $this->submitForm(['configuration[order_summary_view]' => ''], t('Save'));
    $this->drupalGet('/checkout/1');
    // Validate that the default OrderSummary-View is not on the checkout-page.
    $this->assertSession()->elementNotExists('css', '.view-id-commerce_checkout_order_summary');

    // Change OrderSummary to a different View.
    $this->drupalGet('/admin/structure/views/view/commerce_checkout_order_summary/duplicate');
    $this->submitForm(['id' => 'duplicate_of_commerce_checkout_order_summary'], 'Duplicate');
    $this->drupalGet('/admin/commerce/config/checkout-flows/manage/default');
    $this->submitForm(['configuration[order_summary_view]' => 'duplicate_of_commerce_checkout_order_summary'], t('Save'));
    $this->drupalGet('/checkout/1');
    // Validate that the duplicated OrderSummary-View is on the checkout-page.
    $this->assertSession()->elementExists('css', '.view-id-duplicate_of_commerce_checkout_order_summary');
  }

}
