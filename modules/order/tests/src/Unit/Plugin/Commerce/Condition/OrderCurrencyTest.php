<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderCurrency;
use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderCurrency
 * @group commerce
 */
class OrderCurrencyTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEmptyOrder() {
    $condition = new OrderCurrency([
      'currencies' => ['USD'],
    ], 'order_currency', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getTotalPrice()->willReturn(NULL);
    $order = $order->reveal();

    $this->assertFalse($condition->evaluate($order));
  }

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $condition = new OrderCurrency([
      'currencies' => ['USD'],
    ], 'order_currency', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getTotalPrice()->willReturn(new Price('10', 'RSD'));
    $order = $order->reveal();

    $this->assertFalse($condition->evaluate($order));
    $condition->setConfiguration(['currencies' => ['RSD']]);
    $this->assertTrue($condition->evaluate($order));
  }

}
