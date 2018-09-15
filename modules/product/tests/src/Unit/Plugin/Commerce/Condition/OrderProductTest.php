<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Commerce\Condition\OrderProduct;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_product\Plugin\Commerce\Condition\OrderProduct
 * @group commerce
 */
class OrderProductTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();

    $uuid_map = [
      '1' => '62e428e1-88a6-478c-a8c6-a554ca2332ae',
      '2' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
    ];
    $entity_uuid_mapper = $this->prophesize(EntityUuidMapperInterface::class);
    $entity_uuid_mapper->mapToIds('commerce_product', [$uuid_map['1']])->willReturn(['1']);
    $entity_uuid_mapper->mapToIds('commerce_product', [$uuid_map['2']])->willReturn(['2']);
    $entity_uuid_mapper = $entity_uuid_mapper->reveal();

    $configuration = [];
    $configuration['products'] = [
      ['product' => '62e428e1-88a6-478c-a8c6-a554ca2332ae'],
    ];
    $condition = new OrderProduct($configuration, 'order_product', ['entity_type' => 'commerce_order'], $entity_type_manager, $entity_uuid_mapper);

    // Order item with no purchasable entity.
    $first_order_item = $this->prophesize(OrderItemInterface::class);
    $first_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $first_order_item->getPurchasedEntity()->willReturn(NULL);
    $first_order_item = $first_order_item->reveal();

    // Order item with a variation belong to product #2.
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->getEntityTypeId()->willReturn('commerce_product_variation');
    $purchased_entity->getProductId()->willReturn(2);
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

    $configuration['products'][0]['product'] = '30df59bd-7b03-4cf7-bb35-d42fc49f0651';
    $condition->setConfiguration($configuration);

    $order = $this->buildOrder([$first_order_item, $second_order_item]);
    $this->assertTrue($condition->evaluate($order));

    // Test legacy configuration.
    $configuration['products'] = [
      ['product_id' => '2'],
    ];
    $condition->setConfiguration($configuration);
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
