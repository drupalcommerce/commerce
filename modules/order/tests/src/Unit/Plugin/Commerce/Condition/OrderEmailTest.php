<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderEmail;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderCustomerRole
 * @group commerce, orderemail
 */
class OrderEmailTest extends UnitTestCase {

  /**
   * Covers evaluate.
   */
  public function testOrderEmail() {
    $condition = new OrderEmail([
      'mail' => ['test@test.com'],
    ], 'order_mail', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getEmail()->willReturn(NULL);
    $order = $order->reveal();
    $this->assertFalse($condition->evaluate($order));
  }

}
