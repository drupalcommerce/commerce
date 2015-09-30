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
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * @var \Drupal\commerce_store\StoreInterface
   */
  protected $store;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $mockAccountBuilder;

  /**
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $mockCartSessionBuilder;

  /**
   * @var \Drupal\commerce_order\OrderInterface
   */
  protected $mockOrderBuilder;

  /**
   * @var string
   */
  protected $orderType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->mockAccountBuilder = $this->getMockBuilder('Drupal\user\UserInterface');
    $this->mockCartSessionBuilder = $this->getMockBuilder('Drupal\commerce_cart\CartSessionInterface');

    // Entity storage mock for mocking the api functions for getting entity mocks
    // and entity ids.
    $mockEntityStorageBuilder = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface');
    $this->orderStorage = $mockEntityStorageBuilder->getMock();

    // Build multiple Order entity mocks to use.
    $this->mockOrderBuilder = $this->getMockBuilder('Drupal\commerce_order\OrderInterface');

    // Mock the getQuery function in the orderStorage.
    $mockQueryBuilder = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryInterface');
    $this->query = $mockQueryBuilder->getMock();

    $this->orderStorage->expects($this->any())
      ->method('getQuery')
      ->willReturn($this->query);

    // Mock the getStorage to return the $this->orderStorage which mocks the
    // entity storage interface.
    $mockEntityManagerBuilder = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface');
    $this->mockEntityManager = $mockEntityManagerBuilder->getMock();
    $this->mockEntityManager->expects($this->any())
      ->method('getStorage')
      ->with('commerce_order')
      ->willReturn($this->orderStorage);

    // Mock the store entity.
    $mockStoreBuilder = $this->getMockBuilder('Drupal\commerce_store\StoreInterface');
    $this->store = $mockStoreBuilder->getMock();
    $this->store->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->orderType = 'default';

    // TODO: getCartIds is used for two situations, one to validate cart
    // does not exist and to load them. How do we differentiate between these
    // two situations?

    // Split up in different tests? Create/Get

    // TODO: Look into prophecy for phpunit
  }

  /**
   * ::covers createCart
   * ::covers getCart
   */
  public function testCreateAnonymousUserCartProvider() {
    $anonymousUser = $this->mockAccountBuilder->getMock();
    $anonymousUser->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(FALSE);
    $anonymousUser->expects($this->any())
      ->method('isAnonymous')
      ->willReturn(TRUE);
    $anonymousUser->expects($this->any())
      ->method('id')
      ->willReturn(0);

    $firstOrder = $this->mockOrderBuilder->getMock();
    $firstOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn($anonymousUser->id());
    $firstOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->orderType);
    $firstOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $firstOrder->expects($this->any())
      ->method('id')
      ->willReturn(1);

    // Make sure the create returns an order.
    $this->orderStorage->expects($this->any())
      ->method('create')
      ->willReturn($firstOrder);

    // Empty result query for the first query
    $this->query->expects($this->any())
      ->method('execute')
      ->willReturn(array());

    // Mock cart session to get cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();
    $cartSession->expects($this->any())
      ->method('getCartIds')
      ->willReturn(array());

    // This is what we want to test.
    $cartProvider = new CartProvider($this->entityManager, $anonymousUser, $cartSession);

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->orderType, $this->store, $anonymousUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->orderType, $this->store, $anonymousUser);
    $this->assertFalse($cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

  }

  public function testCreateRegisteredUserCartProvider() {
    $registeredUser = $this->mockAccountBuilder->getMock();
    $registeredUser->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(TRUE);
    $registeredUser->expects($this->any())
      ->method('isAnonymous')
      ->willReturn(FALSE);
    $registeredUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $firstOrder = $this->mockOrderBuilder->getMock();
    $firstOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn($registeredUser->id());
    $firstOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->orderType);
    $firstOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $firstOrder->expects($this->any())
      ->method('id')
      ->willReturn(1);

    // Make sure the create returns an order.
    $this->orderStorage->expects($this->any())
      ->method('create')
      ->willReturn($firstOrder);

    // Empty result query for the first query
    $this->query->expects($this->any())
      ->method('execute')
      ->willReturn(array());

    // Mock cart session to get cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();

    // This is what we want to test.
    $cartProvider = new CartProvider($this->entityManager, $registeredUser, $cartSession);

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->orderType, $this->store, $registeredUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->orderType, $this->store, $registeredUser);
    $this->assertFalse($cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

  }

  public function testGetRegisteredUserCartProvider() {
    $randomOrderType = 'randomOrderType';

    $registeredUser = $this->mockAccountBuilder->getMock();
    $registeredUser->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(TRUE);
    $registeredUser->expects($this->any())
      ->method('isAnonymous')
      ->willReturn(FALSE);
    $registeredUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $firstOrder = $this->mockOrderBuilder->getMock();
    $firstOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn($registeredUser->id());
    $firstOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->orderType);
    $firstOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $firstOrder->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $secondOrder = $this->mockOrderBuilder->getMock();
    $secondOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn($registeredUser->id());
    $secondOrder->expects($this->any())
      ->method('getType')
      ->willReturn($randomOrderType);
    $secondOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $secondOrder->expects($this->any())
      ->method('id')
      ->willReturn(2);

    $orders = [
      $firstOrder->id() => $firstOrder,
      $secondOrder->id()=> $secondOrder
    ];

    // Make sure the load returns an order.
    $this->orderStorage->expects($this->any())
      ->method('load')
      ->with($firstOrder->id())
      ->willReturn($firstOrder);

    // Make sure the create returns an order.
    $this->orderStorage->expects($this->any())
      ->method('loadMultiple')
      ->with(array_keys($orders))
      ->willReturn($orders);

    // Empty result query for the first query
    $this->query->expects($this->any())
      ->method('execute')
      ->willReturn(array_keys($orders));

    // Mock cart session to get cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();

    // This is what we want to test.
    $cartProvider = new CartProvider($this->entityManager, $registeredUser, $cartSession);

    $cart = $cartProvider->getCart($this->orderType, $this->store, $registeredUser);
    $cart_id = $cartProvider->getCartId($this->orderType, $this->store, $registeredUser);
    // TODO: Validate the results!

    $carts = $cartProvider->getCarts($registeredUser);
    $cart_ids = $cartProvider->getCartIds($registeredUser);
  }

}
