<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

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
    'node',
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
    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $this->variations[] = $variation;
    $this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store);
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
  }

  /**
   * Test the cart page.
   */
  public function testCartPage() {
    $this->drupalLogin($this->adminUser);

    foreach ($this->variations as $variation) {
      $this->cartManager->addEntity($this->cart, $variation);
    }

    $this->drupalGet('cart');
    // Confirm the presence of the order total summary.
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Subtotal');
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Total');
    $this->assertSession()->pageTextContains('$999.00');
    $this->assertSession()->pageTextContains('$350.00');
    // Confirm the presence and functioning of the Quantity field.
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->buttonExists('Update cart');
    $values = [
      'edit_quantity[0]' => 2,
      'edit_quantity[1]' => 3,
    ];
    $this->submitForm($values, t('Update cart'));
    $this->assertSession()->pageTextContains(t('Your shopping cart has been updated.'));
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 3);
    $this->assertSession()->elementTextContains('css', '.order-total-line', 'Total');
    $this->assertSession()->pageTextContains('$3,048.00');

    // Confirm the presence and functioning of the Remove button.
    $this->assertSession()->buttonExists('Remove');
    $this->submitForm([], t('Remove'));
    $this->submitForm([], t('Remove'));
    $this->assertSession()->pageTextContains(t('Your shopping cart is empty.'));

    // Test that cart is denied for user without permission.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->revokePermission('access cart')
      ->save();
    $this->drupalLogout();
    $this->drupalGet('cart');
    $this->assertSession()->statusCodeEquals(403);
  }

}
