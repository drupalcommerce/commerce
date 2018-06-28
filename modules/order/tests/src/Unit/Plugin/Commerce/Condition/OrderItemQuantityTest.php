<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemQuantity;
use Drupal\commerce_promotion\Entity\PromotionInterface;
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
    $parent_entity = $this->prophesize(PromotionInterface::class);
    $parent_entity = $parent_entity->reveal();

    $condition = new OrderItemQuantity([
      'operator' => $operator,
      'quantity' => $quantity,
    ], 'order_item_quantity', ['entity_type' => 'commerce_order']);
    $condition->setParentEntity($parent_entity);

    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getQuantity()->willReturn($given_quantity);
    $order_item = $order_item->reveal();
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getItems()->willReturn([$order_item]);
    $order = $order->reveal();

    $this->assertEquals($result, $condition->evaluate($order));
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
