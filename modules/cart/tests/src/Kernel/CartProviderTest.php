<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_cart\Exception\DuplicateCartException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreType;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the Cart Provider.
 *
 * @coversDefaultClass \Drupal\commerce_cart\CartProvider
 * @group commerce
 * @group commerce_cart
 */
class CartProviderTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options',
    'entity',
    'views',
    'address',
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
   * The store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * Anonymous user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * Registered user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $registeredUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig(['commerce_order']);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    StoreType::create(['id' => 'animals', 'label' => 'Animals']);
    $store = Store::create([
      'type' => 'animals',
      'name' => 'Llamas and more',
    ]);
    $store->save();
    $this->store = $this->reloadEntity($store);

    $this->anonymousUser = $this->createUser([
      'uid' => 0,
      'name' => '',
      'status' => 0,
    ]);
    $this->registeredUser = $this->createUser();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Installs commerce_cart module.
   *
   * Do to issues with hook_entity_bundle_create, we need to run this manually
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
  }

  /**
   * This test the createCart method in the CartProvider for an anonymous user.
   *
   * It specifically tests the exception thrown when trying to create a cart
   * twice.
   *
   * @covers ::createCart
   */
  public function testCreateAnonymousUserCartProvider() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');

    // Test the createCart method.
    $order_type = 'default';
    $cart = $cartProvider->createCart($order_type, $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    // Recreating a cart again will throw an exception.
    $this->setExpectedException(DuplicateCartException::class);
    $cartProvider->createCart($order_type, $this->store, $this->anonymousUser);
  }

  /**
   * This tests the get methods for the CartProvider with an anonymous user.
   *
   * @covers ::getCart
   * @covers ::getCartId
   * @covers ::getCarts
   * @covers ::getCartIds
   */
  public function testGetAnonymousUserCartProvider() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');

    // Test the getCart method.
    $cart = $cartProvider->createCart('default', $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart, 'The cart is found when an order type, store and user is given.');
    $cart = $cartProvider->getCart('default', $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart, 'The cart is found when an order type, store and user is given.');

    // Test the getCartId method.
    $cart_id = $cartProvider->getCartId('default', $this->store, $this->anonymousUser);
    $this->assertInternalType('int', $cart_id, 'The cart id is when an order type, store and user is given.');
    $this->assertEquals(1, $cart_id, 'The expected cart id 1 for the given order type, store and user.');

    // Test the getCarts method.
    $carts = $cartProvider->getCarts($this->anonymousUser);
    $this->assertContainsOnlyInstancesOf(OrderInterface::class, $carts, 'The carts returned should all be objects that implement the OrderInterface.');

    // Test the getCartIds method.
    $cart_ids = $cartProvider->getCartIds($this->anonymousUser);
    $this->assertContainsOnly('int', $cart_ids, 'The cart ids returned should all be integers.');
    $this->assertContains(1, $cart_ids, 'The cart ids returned should contain an id with value 1.');
  }

  /**
   * This test the createCart method in the CartProvider for a registered user.
   *
   * @covers ::createCart
   */
  public function testCreateRegisteredUserCartProvider() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');

    // Test the createCart method.
    $cart = $cartProvider->createCart('default', $this->store, $this->registeredUser);
    $this->assertInstanceOf(OrderInterface::class, $cart, 'The cart is created for a registered user if createCart returns an Order entity.');

    // Recreating a cart again will throw an exception.
    $this->setExpectedException(DuplicateCartException::class);
    $cartProvider->createCart('default', $this->store, $this->registeredUser);
  }

  /**
   * This tests the get methods for the CartProvider with a registered user.
   *
   * @covers ::getCart
   * @covers ::getCartId
   * @covers ::getCarts
   * @covers ::getCartIds
   */
  public function testGetRegisteredUserCartProvider() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');
    $cartProvider->createCart('default', $this->store, $this->registeredUser);

    // Test the getCart method.
    $cart = $cartProvider->getCart('default', $this->store, $this->registeredUser);
    $this->assertInstanceOf(OrderInterface::class, $cart, 'The cart is found when an order type, store and user is given.');

    // Test the getCartId method.
    $cart_id = $cartProvider->getCartId('default', $this->store, $this->registeredUser);
    $this->assertInternalType('int', $cart_id, 'The cart id is when an order type, store and user is given.');
    $this->assertEquals(1, $cart_id, 'The expected cart id 1 for the given order type, store and user.');

    // Test the getCarts method.
    $carts = $cartProvider->getCarts($this->registeredUser);
    $this->assertContainsOnlyInstancesOf(OrderInterface::class, $carts, 'The carts returned should all be objects that implement the OrderInterface.');

    // Test the getCartIds method.
    $cart_ids = $cartProvider->getCartIds($this->registeredUser);
    $this->assertContainsOnly('int', $cart_ids, 'The cart ids returned should all be integers.');
    $this->assertContains(1, $cart_ids, 'The cart ids returned should contain an id with value 1.');
  }

}
