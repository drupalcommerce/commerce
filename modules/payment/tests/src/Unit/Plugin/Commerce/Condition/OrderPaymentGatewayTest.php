<?php

namespace Drupal\Tests\commerce_payment\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\Condition\OrderPaymentGateway;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_payment\Plugin\Commerce\Condition\OrderPaymentGateway
 * @group commerce
 */
class OrderPaymentGatewayTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testIncompleteOrder() {
    $condition = new OrderPaymentGateway([
      'payment_gateways' => ['test'],
    ], 'order_payment_gateway', ['entity_type' => 'commerce_order']);
    $order = $this->prophesize(OrderInterface::class);

    $entity_reference_item = $this->prophesize(EntityReferenceItem::class);
    $entity_reference_item->isEmpty()->willReturn(TRUE);
    $entity_reference_item = $entity_reference_item->reveal();

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->get('payment_gateway')->willReturn($entity_reference_item);
    $order = $order->reveal();

    $this->assertFalse($condition->evaluate($order));
  }

  /**
   * Covers evaluate.
   */
  public function testOrderPaymentGateway() {
    $entity_reference_item = $this->prophesize(EntityReferenceItem::class);
    $entity_reference_item->isEmpty()->willReturn(FALSE);
    $entity_reference_item->get('target_id')->willReturn('test');
    $entity_reference_item = $entity_reference_item->reveal();

    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->get('payment_gateway')->willReturn($entity_reference_item);
    $order = $order->reveal();

    $condition = new OrderPaymentGateway([
      'payment_gateways' => ['cash_on_delivery'],
    ], 'order_payment_gateway', ['entity_type' => 'commerce_order']);
    $this->assertFalse($condition->evaluate($order));

    $condition = new OrderPaymentGateway([
      'payment_gateways' => ['test', 'cash_on_delivery'],
    ], 'order_payment_gateway', ['entity_type' => 'commerce_order']);
    $this->assertTrue($condition->evaluate($order));
  }

}
