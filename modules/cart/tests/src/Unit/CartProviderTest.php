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
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $mockCartSessionBuilder;

  /**
   * @var \Drupal\commerce_order\OrderInterface
   */
  protected $mockOrderBuilder;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $registeredUser;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

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

    $this->firstOrderType = 'default';
    $this->secondOrderType = 'randomOrderType';

    $this->firstOrder = $this->mockOrderBuilder->getMock();
    $this->firstOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn($this->anonymousUser->id());
    $this->firstOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->firstOrderType);
    $this->firstOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $this->firstOrder->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->secondOrder = $this->mockOrderBuilder->getMock();
    $this->secondOrder->expects($this->any())
      ->method('getOwnerId')
      ->willReturn($this->registeredUser->id());
    $this->secondOrder->expects($this->any())
      ->method('getType')
      ->willReturn($this->secondOrderType);
    $this->secondOrder->expects($this->any())
      ->method('getStoreId')
      ->willReturn($this->store->id());
    $this->secondOrder->expects($this->any())
      ->method('id')
      ->willReturn(2);

  }

  /**
   * ::covers createCart
   * ::covers getCart
   */
  public function testCreateAnonymousUserCartProvider() {

    // Make sure the create returns an order.
    $this->orderStorage->expects($this->any())
      ->method('create')
      ->willReturn($this->firstOrder);

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
    $cartProvider = new CartProvider($this->entityManager, $this->anonymousUser, $cartSession);

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->anonymousUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->anonymousUser);
    $this->assertFalse($cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');
  }

  public function testCreateRegisteredUserCartProvider() {

    // Make sure the create returns an order.
    $this->orderStorage->expects($this->any())
      ->method('create')
      ->willReturn($this->firstOrder);

    // Empty result query for the first query
    $this->query->expects($this->any())
      ->method('execute')
      ->willReturn(array());

    // Mock cart session to get cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();

    // This is what we want to test.
    $cartProvider = new CartProvider($this->entityManager, $this->registeredUser, $cartSession);

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    // Test creating the cart for the anonymous user.
    $cart = $cartProvider->createCart($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertFalse($cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

  }

  public function testGetRegisteredUserCartProvider() {

    $orders = [
      $this->firstOrder->id() => $this->firstOrder,
      $this->secondOrder->id() => $this->secondOrder
    ];

    // TODO: Mock order storage to one without and one with orders
    // Make sure the load returns an order.
    $this->orderStorage->expects($this->any())
      ->method('load')
      ->with($this->firstOrder->id())
      ->willReturn($this->firstOrder);

    // Make sure the create returns an order.
    $this->orderStorage->expects($this->any())
      ->method('loadMultiple')
      ->with(array_keys($orders))
      ->willReturn($orders);

    // TODO: Mock query to one with and without orders
    // Empty result query for the first query
    $this->query->expects($this->any())
      ->method('execute')
      ->willReturn(array_keys($orders));

    // Mock cart session to get cart ids.
    $cartSession = $this->mockCartSessionBuilder->getMock();

    // This is what we want to test.
    $cartProvider = new CartProvider($this->entityManager, $this->registeredUser, $cartSession);

    $cart = $cartProvider->getCart($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertInstanceOf('OrderInterface', $cart, 'The cart is created for an anonymous user if createCart returns an Order entity.');

    $cart_id = $cartProvider->getCartId($this->firstOrderType, $this->store, $this->registeredUser);
    $this->assertInternalType('int', $cart_id, '');
    $this->assertEquals(1, $cart_id, '');

    $carts = $cartProvider->getCarts($this->registeredUser);
    $this->assertContainsOnlyInstancesOf('OrderInterface', $carts, '');

    $cart_ids = $cartProvider->getCartIds($this->registeredUser);
    $this->assertContainsOnly('int', $cart_ids, '');
    $this->assertContainsOnly(1, $cart_ids, '');
    $this->assertContainsOnly(2, $cart_ids, '');
  }

}
