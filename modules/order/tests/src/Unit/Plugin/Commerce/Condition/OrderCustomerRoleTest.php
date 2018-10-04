<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderCustomerRole;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderCustomerRole
 * @group commerce
 */
class OrderCustomerRoleTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $condition = new OrderCustomerRole([
      'roles' => ['merchant'],
    ], 'order_customer_role', ['entity_type' => 'commerce_order']);
    $customer = $this->prophesize(UserInterface::class);
    $customer->getRoles()->willReturn(['authenticated']);
    $customer = $customer->reveal();
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getCustomer()->willReturn($customer);
    $order = $order->reveal();

    $this->assertFalse($condition->evaluate($order));
    $condition->setConfiguration(['roles' => ['authenticated', 'merchant']]);
    $this->assertTrue($condition->evaluate($order));
  }

}
