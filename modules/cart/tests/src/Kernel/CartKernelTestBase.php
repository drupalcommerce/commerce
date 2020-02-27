<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Provides a base class for cart kernel tests.
 */
abstract class CartKernelTestBase extends OrderKernelTestBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManager
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProvider
   */
  protected $cartProvider;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['commerce_cart']);

    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
  }

}
