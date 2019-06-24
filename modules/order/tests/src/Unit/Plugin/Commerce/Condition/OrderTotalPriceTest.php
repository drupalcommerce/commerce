<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderTotalPrice;
use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderTotalPrice
 * @group commerce
 */
class OrderTotalPriceTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEmptyOrder() {
    $condition = new OrderTotalPrice([
      'operator' => '==',
      'amount' => [
        'number' => '10.00',
        'currency_code' => 'EUR',
      ],
    ], 'order_total_price', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getTotalPrice()->willReturn(NULL);
    $order = $order->reveal();

    $this->assertFalse($condition->evaluate($order));
  }

  /**
   * ::covers evaluate.
   */
  public function testMismatchedCurrencies() {
    $condition = new OrderTotalPrice([
      'operator' => '==',
      'amount' => [
        'number' => '10.00',
        'currency_code' => 'EUR',
      ],
    ], 'order_total_price', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getTotalPrice()->willReturn(new Price('10.00', 'USD'));
    $order = $order->reveal();

    $this->assertFalse($condition->evaluate($order));
  }

  /**
   * ::covers evaluate.
   *
   * @dataProvider totalPriceProvider
   */
  public function testEvaluate($operator, $total_price, $given_total_price, $result) {
    $condition = new OrderTotalPrice([
      'operator' => $operator,
      'amount' => [
        'number' => $total_price,
        'currency_code' => 'USD',
      ],
    ], 'order_total_price', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getTotalPrice()->willReturn(new Price($given_total_price, 'USD'));
    $order = $order->reveal();

    $this->assertEquals($result, $condition->evaluate($order));
  }

  /**
   * Data provider for ::testEvaluate.
   *
   * @return array
   *   A list of testEvaluate function arguments.
   */
  public function totalPriceProvider() {
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
