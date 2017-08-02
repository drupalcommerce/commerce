<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\ConditionGroup;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemQuantity;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\ConditionGroup
 * @group commerce
 */
class ConditionGroupTest extends UnitTestCase {

  /**
   * ::covers __construct.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidOperator() {
    $condition_group = new ConditionGroup([], 'INVALID');
  }

  /**
   * ::covers getConditions
   * ::covers getOperator.
   */
  public function testGetters() {
    $conditions = [];
    $conditions[] = new OrderItemQuantity([
      'operator' => '>',
      'quantity' => '10',
    ], 'order_item_quantity', []);

    $condition_group = new ConditionGroup($conditions, 'AND');
    $this->assertEquals($conditions, $condition_group->getConditions());
    $this->assertEquals('AND', $condition_group->getOperator());
  }

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $conditions = [];
    $conditions[] = new OrderItemQuantity([
      'operator' => '>',
      'quantity' => '10',
    ], 'order_item_quantity', ['entity_type' => 'commerce_order_item']);
    $conditions[] = new OrderItemQuantity([
      'operator' => '<',
      'quantity' => '100',
    ], 'order_item_quantity', ['entity_type' => 'commerce_order_item']);
    $first_order_item = $this->prophesize(OrderItemInterface::class);
    $first_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $first_order_item->getQuantity()->willReturn(101);
    $first_order_item = $first_order_item->reveal();

    $second_order_item = $this->prophesize(OrderItemInterface::class);
    $second_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $second_order_item->getQuantity()->willReturn(90);
    $second_order_item = $second_order_item->reveal();

    $empty_condition_group = new ConditionGroup([], 'AND');
    $this->assertTrue($empty_condition_group->evaluate($first_order_item));

    $and_condition_group = new ConditionGroup($conditions, 'AND');
    $this->assertFalse($and_condition_group->evaluate($first_order_item));
    $this->assertTrue($and_condition_group->evaluate($second_order_item));

    $or_condition_group = new ConditionGroup($conditions, 'OR');
    $this->assertTrue($or_condition_group->evaluate($first_order_item));
    $this->assertTrue($or_condition_group->evaluate($second_order_item));
  }

}
