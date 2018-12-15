<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_order\OrderQueryAccessHandler;
use Drupal\entity\QueryAccess\Condition;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;

/**
 * Tests query access filtering for carts.
 *
 * @coversDefaultClass \Drupal\commerce_cart\EventSubscriber\QueryAccessSubscriber
 * @group commerce
 */
class CartQueryAccessTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

  /**
   * The query access handler.
   *
   * @var \Drupal\commerce_order\OrderQueryAccessHandler
   */
  protected $handler;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();

    $this->installCommerceCart();
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type = $entity_type_manager->getDefinition('commerce_order');
    $this->handler = OrderQueryAccessHandler::createInstance($this->container, $entity_type);
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
  }

  /**
   * @covers ::onQueryAccess
   */
  public function testAccess() {
    // User with full access.
    foreach (['administer commerce_order', 'view commerce_order'] as $permission) {
      $user = $this->createUser([], [$permission]);
      $conditions = $this->handler->getConditions('view', $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());
    }

    // Anonymous user with no access other than to their own carts.
    $cart = $this->cartProvider->createCart('default', $this->store);
    $conditions = $this->handler->getConditions('view');
    $expected_conditions = [
      new Condition('order_id', [$cart->id()]),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // Confirm that finalized carts are also allowed.
    $this->cartProvider->finalizeCart($cart);
    $another_cart = $this->cartProvider->createCart('default', $this->store);
    $conditions = $this->handler->getConditions('view');
    $expected_conditions = [
      new Condition('order_id', [$another_cart->id(), $cart->id()]),
    ];
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());
  }

}
