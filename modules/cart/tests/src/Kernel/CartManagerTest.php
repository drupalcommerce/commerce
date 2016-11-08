<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the Cart Manager.
 *
 * @coversDefaultClass \Drupal\commerce_cart\CartManager
 *
 * @group commerce
 */
class CartManagerTest extends EntityKernelTestBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManager
   */
  protected $cart_manager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProvider
   */
  protected $cart_provider;

  /**
   * The store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

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
    'options',
    'entity',
    'entity_reference_revisions',
    'views',
    'address',
    'path',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('commerce_store');
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

    $store = Store::create([
      'type' => 'default',
      'name' => 'Sample store',
      'default_currency' => 'USD',
    ]);
    $store->save();
    $this->store = $this->reloadEntity($store);
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
    $this->cart_provider = $this->container->get('commerce_cart.cart_provider');
    $this->cart_manager = $this->container->get('commerce_cart.cart_manager');
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
    $cart_manager = $this->cart_manager;
    $cart_provider = $this->cart_provider;

    $cart_provider->createCart('default', $this->store, $this->user);
    $cart = $cart_provider->getCart('default', $this->store, $this->user);

    $this->assertInstanceOf(OrderInterface::class, $cart);
    $this->assertEmpty($cart->getItems());

    // Tests CartManager::addEntity.
    $order_item1 = $cart_manager->addEntity($cart, $this->variation1);
    $order_item1->save();
    $order_item1 = $this->reloadEntity($order_item1);
    $this->assertEquals([$order_item1], $cart->getItems());
    $this->assertEquals(1, $order_item1->getQuantity());

    // Test total.
    $this->assertEquals(new Price('1.00', 'USD'), $cart->getTotalPrice());

    // Tests CartManager::updateOrderItem.
    $order_item1->setQuantity(2);
    $order_item1->save();
    $order_item1 = $this->reloadEntity($order_item1);
    $cart_manager->updateOrderItem($cart, $order_item1);
    $this->assertTrue($cart->hasItem($order_item1));
    $this->assertEquals(2, $order_item1->getQuantity());

    // Test total.
    $cart->save();
    $this->assertEquals(new Price('2.00', 'USD'), $cart->getTotalPrice());

    // Tests CartManager::addEntity.
    $order_item2 = $cart_manager->addEntity($cart, $this->variation2, 3);
    $order_item2->save();
    $order_item2 = $this->reloadEntity($order_item2);
    $this->assertTrue($cart->hasItem($order_item1));
    $this->assertTrue($cart->hasItem($order_item2));
    $this->assertEquals(3, $order_item2->getQuantity());

    // Tests CartManager::removeOrderItem.
    $cart_manager->removeOrderItem($cart, $order_item1);
    $this->assertTrue($cart->hasItem($order_item2));
    $this->assertFalse($cart->hasItem($order_item1));

    // Tests CartManager::emptyCart.
    $cart_manager->emptyCart($cart);
    $this->assertEmpty($cart->getItems());
  }

}
