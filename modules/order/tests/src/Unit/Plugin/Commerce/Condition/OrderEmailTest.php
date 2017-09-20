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
    $orderFalse = $this->prophesize(OrderInterface::class);
    $orderFalse->getEntityTypeId()->willReturn('commerce_order');
    $orderFalse->getEmail()->willReturn(NULL);
    $orderFalse = $orderFalse->reveal();
    $this->assertFalse($condition->evaluate($orderFalse));
    $orderTrue = $this->prophesize(OrderInterface::class);
    $orderTrue->getEntityTypeId()->willReturn('commerce_order');
    $orderTrue->setEmail('tests@test.com');
    $orderTrue->getEmail()->willReturn('tests@test.com');
    $orderTrue = $orderTrue->reveal();
    $this->assertTrue($condition->evaluate($orderTrue));

  }

}
