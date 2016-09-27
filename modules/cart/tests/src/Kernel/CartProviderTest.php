<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_cart\Exception\DuplicateCartException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreType;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the cart Provider.
 *
 * @coversDefaultClass \Drupal\commerce_cart\CartProvider
 * @group commerce
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
  protected $authenticatedUser;

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
    $this->authenticatedUser = $this->createUser();

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
   * Tests cart creation for an anonymous user.
   *
   * @covers ::createCart
   */
  public function testCreateAnonymousCart() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');

    $order_type = 'default';
    $cart = $cartProvider->createCart($order_type, $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    // Trying to recreate the same cart should throw an exception.
    $this->setExpectedException(DuplicateCartException::class);
    $cartProvider->createCart($order_type, $this->store, $this->anonymousUser);
  }

  /**
   * Tests getting an anonymous user's cart.
   *
   * @covers ::getCart
   * @covers ::getCartId
   * @covers ::getCarts
   * @covers ::getCartIds
   */
  public function testGetAnonymousCart() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');

    $cartProvider->createCart('default', $this->store, $this->anonymousUser);
    $cart = $cartProvider->getCart('default', $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    $cart_id = $cartProvider->getCartId('default', $this->store, $this->anonymousUser);
    $this->assertEquals(1, $cart_id);

    $carts = $cartProvider->getCarts($this->anonymousUser);
    $this->assertContainsOnlyInstancesOf(OrderInterface::class, $carts);

    $cart_ids = $cartProvider->getCartIds($this->anonymousUser);
    $this->assertContains(1, $cart_ids);
  }

  /**
   * Tests creating a cart for an authenticated user.
   *
   * @covers ::createCart
   */
  public function testCreateAuthenticatedCart() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');

    $cart = $cartProvider->createCart('default', $this->store, $this->authenticatedUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    // Trying to recreate the same cart should throw an exception.
    $this->setExpectedException(DuplicateCartException::class);
    $cartProvider->createCart('default', $this->store, $this->authenticatedUser);
  }

  /**
   * Tests getting an authenticated user's cart.
   *
   * @covers ::getCart
   * @covers ::getCartId
   * @covers ::getCarts
   * @covers ::getCartIds
   */
  public function testGetAuthenticatedCart() {
    $this->installCommerceCart();
    $cartProvider = $this->container->get('commerce_cart.cart_provider');
    $cartProvider->createCart('default', $this->store, $this->authenticatedUser);

    $cart = $cartProvider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    $cart_id = $cartProvider->getCartId('default', $this->store, $this->authenticatedUser);
    $this->assertEquals(1, $cart_id);

    $carts = $cartProvider->getCarts($this->authenticatedUser);
    $this->assertContainsOnlyInstancesOf(OrderInterface::class, $carts);

    $cart_ids = $cartProvider->getCartIds($this->authenticatedUser);
    $this->assertContains(1, $cart_ids);
  }

}
