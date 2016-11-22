<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the cart manager.
 *
 * @coversDefaultClass \Drupal\commerce_cart\CartManager
 * @group commerce
 */
class CartManagerTest extends CommerceKernelTestBase {

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
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation1;

  /**
   * A product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_order');
    $this->installConfig(['commerce_order']);
    $this->installConfig(['commerce_product']);

    $this->variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('1.00', 'USD'),
      'status' => 1,
    ]);

    $this->variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('2.00', 'USD'),
      'status' => 1,
    ]);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * Install commerce cart.
   *
   * Due to issues with hook_entity_bundle_create, we need to run this manually
   * and cannot add commerce_cart to the $modules property.
   *
   * @see https://www.drupal.org/node/2711645
   *
   * @todo patch core so it doesn't explode in Kernel tests.
   */
  protected function installCommerceCart() {
    $this->enableModules(['commerce_cart']);
    $this->installConfig('commerce_cart');
    $this->container->get('entity.definition_update_manager')->applyUpdates();
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
  }

  /**
   * Tests the cart manager.
   *
   * @covers ::addEntity
   * @covers ::createOrderItem
   * @covers ::addOrderItem
   * @covers ::updateOrderItem
   * @covers ::removeOrderItem
   * @covers ::emptyCart
   */
  public function testCartManager() {
    $this->installCommerceCart();

    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);
    $this->assertInstanceOf(OrderInterface::class, $cart);
    $this->assertEmpty($cart->getItems());

    $order_item1 = $this->cartManager->addEntity($cart, $this->variation1);
    $order_item1 = $this->reloadEntity($order_item1);
    $this->assertEquals([$order_item1], $cart->getItems());
    $this->assertEquals(1, $order_item1->getQuantity());
    $this->assertEquals(new Price('1.00', 'USD'), $cart->getTotalPrice());

    $order_item1->setQuantity(2);
    $this->cartManager->updateOrderItem($cart, $order_item1);
    $this->assertTrue($cart->hasItem($order_item1));
    $this->assertEquals(2, $order_item1->getQuantity());
    $this->assertEquals(new Price('2.00', 'USD'), $cart->getTotalPrice());

    $order_item2 = $this->cartManager->addEntity($cart, $this->variation2, 3);
    $order_item2 = $this->reloadEntity($order_item2);
    $this->assertTrue($cart->hasItem($order_item1));
    $this->assertTrue($cart->hasItem($order_item2));
    $this->assertEquals(3, $order_item2->getQuantity());
    $this->assertEquals(new Price('8.00', 'USD'), $cart->getTotalPrice());

    $this->cartManager->removeOrderItem($cart, $order_item1);
    $this->assertTrue($cart->hasItem($order_item2));
    $this->assertFalse($cart->hasItem($order_item1));
    $this->assertEquals(new Price('6.00', 'USD'), $cart->getTotalPrice());

    $this->cartManager->emptyCart($cart);
    $this->assertEmpty($cart->getItems());
    $this->assertEquals(new Price('0.00', 'USD'), $cart->getTotalPrice());
  }

}
