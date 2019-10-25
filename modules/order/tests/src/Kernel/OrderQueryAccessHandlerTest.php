<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_order\OrderQueryAccessHandler;
use Drupal\entity\QueryAccess\Condition;

/**
 * Tests the order query access handler.
 *
 * @coversDefaultClass \Drupal\commerce_order\OrderQueryAccessHandler
 * @group commerce
 */
class OrderQueryAccessHandlerTest extends OrderKernelTestBase {

  /**
   * The query access handler.
   *
   * @var \Drupal\commerce_order\OrderQueryAccessHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type = $entity_type_manager->getDefinition('commerce_order');
    $this->handler = OrderQueryAccessHandler::createInstance($this->container, $entity_type);

    OrderType::create([
      'id' => 'first',
      'label' => 'First',
      'workflow' => 'order_default',
    ])->save();

    OrderType::create([
      'id' => 'second',
      'label' => 'Second',
      'workflow' => 'order_default',
    ])->save();
  }

  /**
   * @covers ::getConditions
   */
  public function testNoAccess() {
    foreach (['view', 'update', 'delete'] as $operation) {
      $user = $this->createUser([], ['access content']);
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertTrue($conditions->isAlwaysFalse());
    }
  }

  /**
   * @covers ::getConditions
   */
  public function testAdmin() {
    foreach (['view', 'update', 'delete'] as $operation) {
      $user = $this->createUser([], ['administer commerce_order']);
      $conditions = $this->handler->getConditions($operation, $user);
      $this->assertEquals(0, $conditions->count());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());
    }
  }

  /**
   * @covers ::getConditions
   */
  public function testView() {
    // Entity type permission.
    $user = $this->createUser([], ['view commerce_order']);
    $conditions = $this->handler->getConditions('view', $user);
    $this->assertEquals(0, $conditions->count());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // Own permission.
    $user = $this->createUser([], ['view own commerce_order']);
    $conditions = $this->handler->getConditions('view', $user);
    $expected_conditions = [
      new Condition('uid', $user->id()),
    ];
    $this->assertEquals('OR', $conditions->getConjunction());
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user', 'user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());

    // Bundle permission.
    $user = $this->createUser([], ['view first commerce_order']);
    $conditions = $this->handler->getConditions('view', $user);
    $expected_conditions = [
      new Condition('type', ['first']),
    ];
    $this->assertEquals('OR', $conditions->getConjunction());
    $this->assertEquals(1, $conditions->count());
    $this->assertEquals($expected_conditions, $conditions->getConditions());
    $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
    $this->assertFalse($conditions->isAlwaysFalse());
  }

  /**
   * @covers ::getConditions
   */
  public function testUpdateDelete() {
    foreach (['update', 'delete'] as $operation) {
      // Bundle permission.
      $user = $this->createUser([], [
        "$operation first commerce_order",
        "$operation second commerce_order",
      ]);
      $conditions = $this->handler->getConditions($operation, $user);
      $expected_conditions = [
        new Condition('type', ['first', 'second']),
      ];
      $this->assertEquals('OR', $conditions->getConjunction());
      $this->assertEquals(1, $conditions->count());
      $this->assertEquals($expected_conditions, $conditions->getConditions());
      $this->assertEquals(['user.permissions'], $conditions->getCacheContexts());
      $this->assertFalse($conditions->isAlwaysFalse());
    }
  }

}
