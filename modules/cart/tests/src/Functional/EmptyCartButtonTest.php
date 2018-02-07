<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Tests the cart page with the empty cart button enabled.
 *
 * @group commerce
 */
class EmptyCartButtonTest extends OrderBrowserTestBase {

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\Order
   */
  protected $cart;

  /**
   * The cart manager for test cart operations.
   *
   * @var \Drupal\commerce_cart\CartManager
   */
  protected $cartManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store);
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
  }

  /**
   * Test the Empty Cart button.
   */
  public function testEmptyCartButton() {
    $this->drupalLogin($this->adminUser);

    // Need a second product to make sure all are removed.
    $second_variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 222,
        'currency_code' => 'USD',
      ],
    ]);
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$second_variation],
    ]);

    $this->cartManager->addEntity($this->cart, $this->variation);
    $this->cartManager->addEntity($this->cart, $second_variation);

    $this->drupalGet('test-empty-cart-button-form/' . $this->cart->id());
    $this->assertSession()->pageTextContains('$999.00');
    $this->assertSession()->pageTextContains('$222.00');
    $this->assertSession()->buttonExists('Empty cart');
    $this->submitForm([], t('Empty cart'));
    $this->assertSession()->pageTextContains(t('Your shopping cart is empty.'));
  }

}
