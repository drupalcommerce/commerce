<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\Tests\commerce_cart\Traits\CartBrowserTestTrait;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;
use Drupal\Tests\commerce_product\Traits\ProductAttributeTestTrait;

/**
 * Defines base class for commerce_cart test cases.
 */
abstract class CartBrowserTestBase extends OrderBrowserTestBase {

  use CartBrowserTestTrait;
  use ProductAttributeTestTrait;

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

    $this->cart = $this->container->get('commerce_cart.cart_provider')->createCart('default');
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $this->attributeFieldManager = $this->container->get('commerce_product.attribute_field_manager');
  }

}
