<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Tests the cart page.
 *
 * @group commerce
 */
class CartTest extends OrderBrowserTestBase {

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
   * An array of variations.
   *
   * @var array
   */
  protected $variations;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
    'commerce_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->variations = [$this->variation];
    // Create an additional variation in order to test updating multiple
    // quantities in cart.
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 350,
        'currency_code' => 'USD',
      ],
    ]);
    // Add a test variation that shouldn't be available.
    $test_variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'price' => [
        'number' => 500,
        'currency_code' => 'USD',
      ],
    ]);
    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation, $test_variation],
    ]);
    $this->variations[] = $variation;
    $this->variations[] = $test_variation;
    $this->cart = $this->container->get('commerce_cart.cart_provider')->createCart('default');
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');

    // Add variations to the cart.
    foreach ($this->variations as $variation) {
      $this->cartManager->addEntity($this->cart, $variation, '1', TRUE, FALSE);
    }
    $this->cart->setRefreshState(OrderInterface::REFRESH_SKIP);
    $this->cart->save();
  }

  /**
   * Test the basic functioning of the cart page.
   */
  public function testCartPage() {
    $this->drupalGet('cart');
    // Confirm the presence of the order total summary.
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Subtotal');
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Total');
    $this->assertSession()->pageTextContains('$999.00');
    $this->assertSession()->pageTextContains('$350.00');
    $this->assertSession()->pageTextContains('$500.00');
    // Confirm the presence and functioning of the Quantity field.
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 1);
    $this->assertSession()->buttonExists('Update cart');
    $values = [
      'edit_quantity[0]' => 2,
      'edit_quantity[1]' => 3,
      'edit_quantity[2]' => 3,
    ];
    $this->submitForm($values, t('Update cart'));
    $this->assertSession()->pageTextContains(sprintf('%s is not available with a quantity of %s.', $this->variations[2]->label(), 3));
    $this->getSession()->getPage()->findButton('edit-remove-button-2')->press();
    $values = [
      'edit_quantity[0]' => 2,
      'edit_quantity[1]' => 3,
    ];
    $this->submitForm($values, t('Update cart'));
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 3);
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Total');
    $this->assertSession()->pageTextContains('$3,048.00');

    // Confirm that setting the quantity to 0 removes an item.
    $values = [
      'edit_quantity[0]' => 0,
      'edit_quantity[1]' => 3,
    ];
    $this->submitForm($values, t('Update cart'));
    $this->assertSession()->pageTextContains(t('Your shopping cart has been updated.'));
    $this->assertSession()->fieldExists('edit-edit-quantity-0');
    $this->assertSession()->fieldNotExists('edit-edit-quantity-1');
    $this->assertSession()->pageTextContains('$1,050.00');

    // Confirm the presence and functioning of the Remove button.
    $this->assertSession()->buttonExists('Remove');
    $this->submitForm([], t('Remove'));
    $this->assertSession()->pageTextContains(t('Your shopping cart is empty.'));
  }

  /**
   * Tests the Checkout button added by commerce_checkout.
   */
  public function testCheckoutButton() {
    $this->drupalGet('cart');
    // Confirm that the "Checkout" button redirects and updates the cart.
    $this->assertSession()->buttonExists('Checkout');
    $values = [
      'edit_quantity[0]' => 2,
      'edit_quantity[1]' => 3,
    ];
    $this->submitForm($values, t('Checkout'));
    $this->assertSession()->addressEquals('checkout/1/order_information');
    $this->assertSession()->pageTextNotContains(t('Your shopping cart has been updated.'));

    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 3);
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Total');
    $this->assertSession()->pageTextContains('$3,048.00');
  }

}
