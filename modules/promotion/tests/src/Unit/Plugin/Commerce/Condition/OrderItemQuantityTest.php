<?php

namespace Drupal\Tests\commerce_promotion\Unit\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_promotion\Plugin\Commerce\Condition\OrderItemQuantity;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemPromotionOfferInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_promotion\Plugin\Commerce\Condition\OrderItemQuantity
 * @group commerce
 */
class OrderItemQuantityTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   *
   * @dataProvider quantityProvider
   */
  public function testEvaluate($operator, $quantity, $given_quantity, $result) {
    $first_order_item = $this->prophesize(OrderItemInterface::class);
    $first_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $first_order_item->getQuantity()->willReturn($given_quantity);
    $first_order_item = $first_order_item->reveal();

    // The second order item's quantity should not be counted.
    $second_order_item = $this->prophesize(OrderItemInterface::class);
    $second_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $second_order_item->getQuantity()->willReturn('1000');
    $second_order_item = $second_order_item->reveal();

    $condition = $this->prophesize(ConditionInterface::class);
    $condition->evaluate($first_order_item)->willReturn(TRUE);
    $condition->evaluate($second_order_item)->willReturn(FALSE);

    $offer = $this->prophesize(OrderItemPromotionOfferInterface::class);
    $offer->getConditions()->willReturn([$condition]);
    $offer->getConditionOperator()->willReturn('OR');
    $offer = $offer->reveal();

    $parent_entity = $this->prophesize(PromotionInterface::class);
    $parent_entity->getOffer()->willReturn($offer);
    $parent_entity = $parent_entity->reveal();

    $condition = new OrderItemQuantity([
      'operator' => $operator,
      'quantity' => $quantity,
    ], 'order_item_quantity', ['entity_type' => 'commerce_order']);
    $condition->setParentEntity($parent_entity);

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getItems()->willReturn([$first_order_item, $second_order_item]);
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
