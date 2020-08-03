<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderPurchasedEntity;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderPurchasedEntity
 * @group commerce
 */
class OrderPurchasedEntityTest extends UnitTestCase {

  /**
   * @covers ::evaluate
   */
  public function testEvaluate() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->id()->willReturn('commerce_product_variation');
    $entity_type_manager->getDefinition('commerce_product_variation')->willReturn($entity_type->reveal());
    $entity_type_manager = $entity_type_manager->reveal();

    $entity_uuid_mapper = $this->prophesize(EntityUuidMapperInterface::class);
    $entity_uuid_mapper = $entity_uuid_mapper->reveal();

    $configuration = [];
    $configuration['entities'] = ['62e428e1-88a6-478c-a8c6-a554ca2332ae'];
    $condition = new OrderPurchasedEntity($configuration, 'commerce_purchased_entity', [
      'entity_type' => 'commerce_order',
      'purchasable_entity_type' => 'commerce_product_variation',
    ], $entity_type_manager, $entity_uuid_mapper);

    // Order item with no purchasable entity.
    $first_order_item = $this->prophesize(OrderItemInterface::class);
    $first_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $first_order_item->getPurchasedEntity()->willReturn(NULL);
    $first_order_item = $first_order_item->reveal();

    // Order item with the second variation.
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->getEntityTypeId()->willReturn('commerce_product_variation');
    $purchased_entity->uuid()->willReturn('30df59bd-7b03-4cf7-bb35-d42fc49f0651');
    $purchased_entity = $purchased_entity->reveal();
    $second_order_item = $this->prophesize(OrderItemInterface::class);
    $second_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $second_order_item->getPurchasedEntity()->willReturn($purchased_entity);
    $second_order_item = $second_order_item->reveal();

    $order = $this->buildOrder([$first_order_item]);
    $this->assertFalse($condition->evaluate($order));

    $order = $this->buildOrder([$second_order_item]);
    $this->assertFalse($condition->evaluate($order));

    $order = $this->buildOrder([$first_order_item, $second_order_item]);
    $this->assertFalse($condition->evaluate($order));

    $configuration['entities'] = ['30df59bd-7b03-4cf7-bb35-d42fc49f0651'];
    $condition->setConfiguration($configuration);

    $order = $this->buildOrder([$first_order_item, $second_order_item]);
    $this->assertTrue($condition->evaluate($order));
  }

  /**
   * Builds a mock order with the given order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The order items.
   *
   * @return object
   *   The mock order.
   */
  protected function buildOrder(array $order_items) {
    $order = $this->prophesize(OrderInterface::class);
    $order->getEntityTypeId()->willReturn('commerce_order');
    $order->getItems()->wilLReturn($order_items);
    $order = $order->reveal();

    return $order;
  }

}
