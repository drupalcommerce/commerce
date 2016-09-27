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

    $this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store);
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
  }

  /**
   * Test the cart page.
   */
  public function testCartPage() {
    $this->drupalLogin($this->adminUser);

    $this->cartManager->addEntity($this->cart, $this->variation);

    $this->drupalGet('cart');
    // Confirm the presence and functioning of the Quantity field.
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->buttonExists('Update cart');
    $values = [
      'edit_quantity[0]' => 2,
    ];
    $this->submitForm($values, t('Update cart'));
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);

    // Confirm the presence and functioning of the Remove button.
    $this->assertSession()->buttonExists('Remove');
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
