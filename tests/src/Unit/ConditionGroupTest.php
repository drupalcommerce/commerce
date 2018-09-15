<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\ConditionGroup;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderTotalPrice;
use Drupal\commerce_price\Price;
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
    $conditions[] = new OrderTotalPrice([
      'operator' => '>',
      'amount' => [
        'number' => '10',
        'currency_code' => 'USD',
      ],
    ], 'order_total_price', ['entity_type' => 'commerce_order']);

    $condition_group = new ConditionGroup($conditions, 'AND');
    $this->assertEquals($conditions, $condition_group->getConditions());
    $this->assertEquals('AND', $condition_group->getOperator());
  }

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $conditions = [];
    $conditions[] = new OrderTotalPrice([
      'operator' => '>',
      'amount' => [
        'number' => '10',
        'currency_code' => 'USD',
      ],
    ], 'order_total_price', ['entity_type' => 'commerce_order']);
    $conditions[] = new OrderTotalPrice([
      'operator' => '<',
      'amount' => [
        'number' => '100',
        'currency_code' => 'USD',
      ],
    ], 'order_total_price', ['entity_type' => 'commerce_order']);
    $first_order = $this->prophesize(OrderInterface::class);
    $first_order->getEntityTypeId()->willReturn('commerce_order');
    $first_order->getTotalPrice()->willReturn(new Price('101', 'USD'));
    $first_order = $first_order->reveal();

    $second_order_item = $this->prophesize(OrderInterface::class);
    $second_order_item->getEntityTypeId()->willReturn('commerce_order');
    $second_order_item->getTotalPrice()->willReturn(new Price('90', 'USD'));
    $second_order_item = $second_order_item->reveal();

    $empty_condition_group = new ConditionGroup([], 'AND');
    $this->assertTrue($empty_condition_group->evaluate($first_order));

    $and_condition_group = new ConditionGroup($conditions, 'AND');
    $this->assertFalse($and_condition_group->evaluate($first_order));
    $this->assertTrue($and_condition_group->evaluate($second_order_item));

    $or_condition_group = new ConditionGroup($conditions, 'OR');
    $this->assertTrue($or_condition_group->evaluate($first_order));
    $this->assertTrue($or_condition_group->evaluate($second_order_item));
  }

}
