<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_cart\Exception\DuplicateCartException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;

/**
 * Tests the cart provider.
 *
 * @coversDefaultClass \Drupal\commerce_cart\CartProvider
 * @group commerce
 */
class CartProviderTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

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

    $this->installEntitySchema('profile');
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
   * Tests cart creation for an anonymous user.
   *
   * @covers ::createCart
   */
  public function testCreateAnonymousCart() {
    $this->installCommerceCart();
    $cart_provider = $this->container->get('commerce_cart.cart_provider');

    $order_type = 'default';
    $cart = $cart_provider->createCart($order_type, $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    // Trying to recreate the same cart should throw an exception.
    $this->setExpectedException(DuplicateCartException::class);
    $cart_provider->createCart($order_type, $this->store, $this->anonymousUser);
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
    $cart_provider = $this->container->get('commerce_cart.cart_provider');

    $cart_provider->createCart('default', $this->store, $this->anonymousUser);
    $cart = $cart_provider->getCart('default', $this->store, $this->anonymousUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    $cart_id = $cart_provider->getCartId('default', $this->store, $this->anonymousUser);
    $this->assertEquals(1, $cart_id);

    $carts = $cart_provider->getCarts($this->anonymousUser);
    $this->assertContainsOnlyInstancesOf(OrderInterface::class, $carts);

    $cart_ids = $cart_provider->getCartIds($this->anonymousUser);
    $this->assertContains(1, $cart_ids);
  }

  /**
   * Tests creating a cart for an authenticated user.
   *
   * @covers ::createCart
   */
  public function testCreateAuthenticatedCart() {
    $this->installCommerceCart();
    $cart_provider = $this->container->get('commerce_cart.cart_provider');

    $cart = $cart_provider->createCart('default', $this->store, $this->authenticatedUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    // Trying to recreate the same cart should throw an exception.
    $this->setExpectedException(DuplicateCartException::class);
    $cart_provider->createCart('default', $this->store, $this->authenticatedUser);
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
    $cart_provider = $this->container->get('commerce_cart.cart_provider');
    $cart_provider->createCart('default', $this->store, $this->authenticatedUser);

    $cart = $cart_provider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertInstanceOf(OrderInterface::class, $cart);

    $cart_id = $cart_provider->getCartId('default', $this->store, $this->authenticatedUser);
    $this->assertEquals(1, $cart_id);

    $carts = $cart_provider->getCarts($this->authenticatedUser);
    $this->assertContainsOnlyInstancesOf(OrderInterface::class, $carts);

    $cart_ids = $cart_provider->getCartIds($this->authenticatedUser);
    $this->assertContains(1, $cart_ids);
  }

  /**
   * Tests finalizing a cart.
   *
   * @covers ::finalizeCart
   */
  public function testFinalizeCart() {
    $this->installCommerceCart();
    $cart_provider = $this->container->get('commerce_cart.cart_provider');
    $cart = $cart_provider->createCart('default', $this->store, $this->authenticatedUser);

    $cart_provider->finalizeCart($cart);
    $cart = $this->reloadEntity($cart);
    $this->assertEmpty($cart->cart->value);

    $cart = $cart_provider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertNull($cart);
  }

  /**
   * Tests cart validation.
   *
   * @covers ::getCartIds
   * @covers ::clearCaches
   */
  public function testCartValidation() {
    $this->installCommerceCart();
    /** @var \Drupal\commerce_cart\CartProviderInterface $cart_provider */
    $cart_provider = $this->container->get('commerce_cart.cart_provider');

    // Locked carts should not be returned.
    $cart = $cart_provider->createCart('default', $this->store, $this->authenticatedUser);
    $cart->lock();
    $cart->save();
    $cart_provider->clearCaches();
    $cart = $cart_provider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertNull($cart);

    // Carts that are no longer carts should not be returned.
    $cart = $cart_provider->createCart('default', $this->store, $this->authenticatedUser);
    $cart->cart = FALSE;
    $cart->save();
    $cart_provider->clearCaches();
    $cart = $cart_provider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertNull($cart);

    // Carts assigned to a different user should not be returned.
    $cart = $cart_provider->createCart('default', $this->store, $this->authenticatedUser);
    $cart->uid = $this->anonymousUser->id();
    $cart->save();
    $cart_provider->clearCaches();
    $cart = $cart_provider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertNull($cart);

    // Canceled carts should not be returned.
    $cart = $cart_provider->createCart('default', $this->store, $this->authenticatedUser);
    $cart->state = 'canceled';
    $cart->save();
    $cart_provider->clearCaches();
    $cart = $cart_provider->getCart('default', $this->store, $this->authenticatedUser);
    $this->assertNull($cart);
  }

}
