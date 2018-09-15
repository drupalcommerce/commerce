<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderEmail;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderEmail
 * @group commerce
 */
class OrderEmailTest extends UnitTestCase {

  /**
   * Covers evaluate.
   */
  public function testOrderEmail() {
    $condition = new OrderEmail([
      'mail' => 'tests@test.com',
    ], 'order_mail', ['entity_type' => 'commerce_order']);
    $order1 = $this->prophesize(OrderInterface::class);
    $order1->getEntityTypeId()->willReturn('commerce_order');
    $order1->getEmail()->willReturn(NULL);
    $order1 = $order1->reveal();

    $order2 = $this->prophesize(OrderInterface::class);
    $order2->getEntityTypeId()->willReturn('commerce_order');
    $order2->getEmail()->willReturn('tests@test.com');
    $order2 = $order2->reveal();

    $this->assertFalse($condition->evaluate($order1));
    $this->assertTrue($condition->evaluate($order2));
  }

}
