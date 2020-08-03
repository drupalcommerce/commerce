<?php

namespace Drupal\Tests\commerce_order\Unit\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemPurchasedEntity;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemPurchasedEntity
 * @group commerce
 */
class OrderItemPurchasedEntityTest extends UnitTestCase {

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
    $condition = new OrderItemPurchasedEntity($configuration, 'order_item_variation', [
      'entity_type' => 'commerce_order_item',
      'purchasable_entity_type' => 'commerce_product_variation',
    ], $entity_type_manager, $entity_uuid_mapper);

    // Order item with no purchasable entity.
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn(NULL);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    // Order item with the second variation.
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->getEntityTypeId()->willReturn('commerce_product_variation');
    $purchased_entity->uuid()->willReturn('30df59bd-7b03-4cf7-bb35-d42fc49f0651');
    $purchased_entity = $purchased_entity->reveal();
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn($purchased_entity);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    $configuration['entities'] = ['30df59bd-7b03-4cf7-bb35-d42fc49f0651'];
    $condition->setConfiguration($configuration);
    $this->assertTrue($condition->evaluate($order_item));
  }

}
