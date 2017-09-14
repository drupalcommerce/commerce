<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderType
 * @group commerce
 */
class OrderTypeTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();
    $condition = new OrderType([
      'bundles' => ['default'],
    ], 'order_type', ['entity_type' => 'commerce_order'], $entity_type_manager);

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->bundle()->willReturn('default');
    $order = $order->reveal();
    $this->assertTrue($condition->evaluate($order));

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->bundle()->willReturn('digital');
    $order = $order->reveal();
    $this->assertFalse($condition->evaluate($order));
  }

}
