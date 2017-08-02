<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemQuantity;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemQuantity
 * @group commerce
 */
class OrderItemQuantityTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   *
   * @dataProvider quantityProvider
   */
  public function testEvaluate($operator, $quantity, $given_quantity, $result) {
    $condition = new OrderItemQuantity([
      'operator' => $operator,
      'quantity' => $quantity,
    ], 'order_item_quantity', ['entity_type' => 'commerce_order_item']);
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getQuantity()->willReturn($given_quantity);
    $order_item = $order_item->reveal();

    $this->assertEquals($result, $condition->evaluate($order_item));
  }

  /**
   * Data provider for ::testEvaluate.
   *
   * @return array
   *   A list of testEvaluate function arguments.
   */
  public function quantityProvider() {
    return [
      ['>', 10, 5, FALSE],
      ['>', 10, 10, FALSE],
      ['>', 10, 11, TRUE],

      ['>=', 10, 5, FALSE],
      ['>=', 10, 10, TRUE],
      ['>=', 10, 11, TRUE],

      ['<', 10, 5, TRUE],
      ['<', 10, 10, FALSE],
      ['<', 10, 11, FALSE],

      ['<=', 10, 5, TRUE],
      ['<=', 10, 10, TRUE],
      ['<=', 10, 11, FALSE],

      ['==', 10, 5, FALSE],
      ['==', 10, 10, TRUE],
      ['==', 10, 11, FALSE],
    ];
  }

}
