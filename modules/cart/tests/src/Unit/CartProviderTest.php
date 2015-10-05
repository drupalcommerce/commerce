<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Tests\CartProviderTest.
 */

namespace Drupal\commerce_cart\Tests;

use Drupal\commerce_cart\CartProvider;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\AvailabilityManager
 * @group commerce
 * @group commerce_cart
 */
class CartProviderTest extends UnitTestCase {

  /**
   * @var \Drupal\commerce_store\StoreInterface
   */
  protected $store;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $getEntityManager;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $createEntityManager;

  /**
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $mockCartSessionBuilder;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $registeredUser;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * @var \Drupal\commerce_store\StoreInterface
   */
  protected $firstOrder;

  /**
   * @var \Drupal\commerce_store\StoreInterface
   */
  protected $secondOrder;

  /**
   * @var string
   */
  protected $firstOrderType;

  /**
   * @var string
   */
  protected $secondOrderType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // We need a registered user and an anonymous user to create the tests.
    $mockAccountBuilder = $this->getMockBuilder('Drupal\user\UserInterface');

    $this->anonymousUser = $mockAccountBuilder->getMock();
    $this->anonymousUser->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(FALSE);
    $this->anonymousUser->expects($this->any())
      ->method('isAnonymous')
      ->willReturn(TRUE);
    $this->anonymousUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->registeredUser = $mockAccountBuilder->getMock();
    $this->registeredUser->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(TRUE);
    $this->registeredUser->expects($this->any())
      ->method('isAnonymous')
      ->willReturn(FALSE);
    $this->registeredUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    // Mock the store entity.
    $mockStoreBuilder = $this->getMockBuilder('Drupal\commerce_store\StoreInterface');
    $this->store = $mockStoreBuilder->getMock();
    $this->store->expects($this->any())
      ->method('id')
      ->willReturn(1);

    // MockBuilder to create mocked instances of the OrderInterface.
    $mockOrderBuilder = $this->getMockBuilder('Drupal\commerce_order\OrderInterface');

    // Some order type string we use to differentiate between carts.
    $this->firstOrderType = 'default';
    $this->secondOrderType = 'randomOrderType';

    // We need an order the test if we can create and get a cart.
    $this->firstOrder = $mockOrderBuilder->getMock();
    $this->firstOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn(1);
    $this->firstOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->firstOrderType);
    $this->firstOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $this->firstOrder->expects($this->any())
      ->method('id')
      ->willReturn(1);

    // We need a second order for the getCarts and getCartIds method tests.
    $this->secondOrder = $mockOrderBuilder->getMock();
    $this->secondOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn(1);
    $this->secondOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->secondOrderType);
    $this->secondOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $this->secondOrder->expects($this->any())
      ->method('id')
      ->willReturn(2);

    // Put the orders in an array for the get mock methods.
    $orders = [
      $this->firstOrder->id() => $this->firstOrder,
      $this->secondOrder->id() => $this->secondOrder,
    ];

    // We need this Builder for the CartSessions that is needed during testing.
    $this->mockCartSessionBuilder = $this->getMockBuilder('Drupal\commerce_cart\CartSessionInterface');

    // Mock the getQuery function in the orderStorage.
    $mockQueryBuilder = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryInterface');

    // query object that returns no cart ids.
    $queryWithOutOrders = $mockQueryBuilder->getMock();
    $queryWithOutOrders->expects($this->any())
      ->method('execute')
      ->willReturn([]);

    // Mock the query class that returns cart ids.
    $queryWithOrders = $mockQueryBuilder->getMock();
    $queryWithOrders->expects($this->any())
      ->method('execute')
      ->willReturn(array_keys($orders));

    // Entity storage mock builders to mock the get and create cart tests.
    $mockEntityStorageBuilder = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface');

    // Create the orderStorage for the createCart tests.
    $createOrderStorage = $mockEntityStorageBuilder->getMock();

    // We want to mock the query that returns no cart ids.
    $createOrderStorage->expects($this->any())
      ->method('getQuery')
      ->willReturn($queryWithOutOrders);

    // Make sure the create returns an order.
    $createOrderStorage->expects($this->any())
      ->method('create')
      ->willReturn($this->firstOrder);

    // Create the orderStorage for the get method tests.
    $getOrderStorage = $mockEntityStorageBuilder->getMock();

    // We want to be able to mock the query that returns cart ids.
    $getOrderStorage->expects($this->any())
      ->method('getQuery')
      ->willReturn($queryWithOrders);

    // Make sure the load of the getOrderStorage returns an order.
    $getOrderStorage->expects($this->any())
      ->method('load')
      ->with($this->firstOrder->id())
      ->willReturn($this->firstOrder);

    // Make sure the loadMultiple returns both orders.
    $getOrderStorage->expects($this->any())
      ->method('loadMultiple')
      ->with(array_keys($orders))
      ->willReturn($orders);

    // Mock the entityManager for the get and create tests.
    $mockEntityManagerBuilder = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface');

    $this->createEntityManager = $mockEntityManagerBuilder->getMock();
    $this->createEntityManager->expects($this->any())
      ->method('getStorage')
      ->with('commerce_order')
      ->willReturn($createOrderStorage);

    $this->getEntityManager = $mockEntityManagerBuilder->getMock();
    $this->getEntityManager->expects($this->any())
      ->method('getStorage')
      ->with('commerce_order')
      ->willReturn($getOrderStorage);
  }

  /**
   * This test the createCart method in the CartProvider for an anonymous user.
   *
   * @covers CartProvider::createCart()
   */
  public function testCreateAnonymousUserCartProvider() {

    // Mock cart session.
    $cartSession = $this->mockCartSessionBuilder->getMock();
    $cartSession->expects($this->any())
      ->method('getCartIds')
      ->willReturn([]);

    // This is the class we want to test.
    $cartProvider = new CartProvider($this->entityManager, $this->anonymousUser, $cartSession);

    // Test the createCart method.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->anonymousUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    // Test recreating the cart again with the createCart method.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->anonymousUser);
    $this->assertFalse($cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');
  }

  /**
   * This tests the get methods for the CartProvider with an anonymous user.
   *
   * @covers CartProvider::getCart()
   * @covers CartProvider::getCartId()
   * @covers CartProvider::getCarts()
   * @covers CartProvider::getCartIds()
   */
  public function testGetAnonymousUserCartProvider() {

    // Mock cart session to get relevant cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();
    $cartSession->expects($this->any())
      ->method('getCartIds')
      ->willReturn([$this->firstOrder->id(), $this->secondOrder->id()]);

    // This is the class we want to test.
    $cartProvider = new CartProvider($this->getEntityManager, $this->anonymousUser, $cartSession);

    // Test the getCart method.
    $cart = $cartProvider->getCart($this->firstOrderType, $this->store, $this->anonymousUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is found when an order type, store and user is given.');

    // Test the getCartId method.
    $cart_id = $cartProvider->getCartId($this->firstOrderType, $this->store, $this->anonymousUser);
    $this->assertInternalType('int', $cart_id, 'The cart id is when an order type, store and user is given.');
    $this->assertEquals(1, $cart_id, 'The expected cart id 1 for the given order type, store and user.');

    // Test the getCarts method.
    $carts = $cartProvider->getCarts($this->anonymousUser);
    $this->assertContainsOnlyInstancesOf('OrderInterface', $carts, 'The carts returned should all be objects that implement the OrderInterface.');

    // Test the getCartIds method.
    $cart_ids = $cartProvider->getCartIds($this->anonymousUser);
    $this->assertContainsOnly('int', $cart_ids, 'The cart ids returned should all be integers.');
    $this->assertContains(1, $cart_ids, 'The cart ids returned should contain an id with value 1.');
    $this->assertContains(2, $cart_ids, 'The cart ids returned should contain an id with value 2.');
  }

  /**
   * This test the createCart method in the CartProvider for a registered user.
   *
   * @covers CartProvider::createCart()
   */
  public function testCreateRegisteredUserCartProvider() {

    // Mock cart session.
    $cartSession = $this->mockCartSessionBuilder->getMock();

    // This is the class we want to test.
    $cartProvider = new CartProvider($this->entityManager, $this->registeredUser, $cartSession);

    // Test the createCart method.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    // Test recreating the cart again with the createCart method.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertFalse($cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

  }

  /**
   * This tests the get methods for the CartProvider with a registered user.
   *
   * @covers CartProvider::getCart()
   * @covers CartProvider::getCartId()
   * @covers CartProvider::getCarts()
   * @covers CartProvider::getCartIds()
   */
  public function testGetRegisteredUserCartProvider() {
    // Mock cart session to get cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();

    // This is what we want to test.
    $cartProvider = new CartProvider($this->getEntityManager, $this->registeredUser, $cartSession);

    // Test the getCart method.
    $cart = $cartProvider->getCart($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is found when an order type, store and user is given.');

    // Test the getCartId method.
    $cart_id = $cartProvider->getCartId($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertInternalType('int', $cart_id, 'The cart id is when an order type, store and user is given.');
    $this->assertEquals(1, $cart_id, 'The expected cart id 1 for the given order type, store and user.');

    // Test the getCarts method.
    $carts = $cartProvider->getCarts($this->registeredUser);
    $this->assertContainsOnlyInstancesOf('OrderInterface', $carts, 'The carts returned should all be objects that implement the OrderInterface.');

    // Test the getCartIds method.
    $cart_ids = $cartProvider->getCartIds($this->registeredUser);
    $this->assertContainsOnly('int', $cart_ids, 'The cart ids returned should all be integers.');
    $this->assertContains(1, $cart_ids, 'The cart ids returned should contain an id with value 1.');
    $this->assertContains(2, $cart_ids, 'The cart ids returned should contain an id with value 2.');
  }

}
