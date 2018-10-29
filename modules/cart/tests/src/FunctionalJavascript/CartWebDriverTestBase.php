<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\Tests\commerce_cart\Functional\CartBrowserTestTrait;
use Drupal\Tests\commerce_order\FunctionalJavascript\OrderWebDriverTestBase;

/**
 * Defines base class for commerce_cart test cases.
 */
abstract class CartWebDriverTestBase extends OrderWebDriverTestBase {

  use CartBrowserTestTrait;

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
    'commerce_cart_test',
    'node',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
      'access content',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default');
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
    $this->attributeFieldManager = \Drupal::service('commerce_product.attribute_field_manager');
  }

}
