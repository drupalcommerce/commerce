<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Tests\CartTest.
 */

namespace Drupal\commerce_cart\Tests;

use Drupal\commerce_order\Tests\OrderTestBase;

/**
 * Tests the cart page.
 *
 * @group commerce
 */
class CartTest extends OrderTestBase {

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
      'administer products',
      'access content',
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
    $this->cartManager->addEntity($this->cart, $this->variation);

    $this->drupalGet('cart');
    // Confirm the presence and functioning of the Quantity field.
    $this->assertFieldByXPath("//input[starts-with(@id, 'edit-edit-quantity')]", NULL, 'Quantity field present.');
    $this->assertFieldByXPath("//input[starts-with(@id, 'edit-edit-quantity')]", 1, 'Quantity field has correct number of items.');
    $this->assertField("edit-submit", 'Update cart button is present.');
    $values = [
      'edit_quantity[0]' => 2,
    ];
    $this->drupalPostForm(NULL, $values, t('Update cart'));
    $this->assertFieldByXPath("//input[starts-with(@id, 'edit-edit-quantity')]", 2, 'Cart updated with new quantity.');

    // Confirm the presence and functioning of the Remove button.
    $this->assertFieldByXPath("//input[starts-with(@id, 'edit-remove-button')]", NULL, 'Remove button is present.');
    $this->drupalPostForm(NULL, array(), t('Remove'));
    $this->assertText(t('Your shopping cart is empty.'), 'Product removed, cart empty.');
  }

}
