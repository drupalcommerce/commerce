<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemProduct;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemProduct
 * @group commerce
 */
class OrderItemProductTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();
    $configuration = [];
    $configuration['products'] = [
      ['product_id' => 1],
    ];
    $condition = new OrderItemProduct($configuration, 'order_item_product', ['entity_type' => 'commerce_order_item'], $entity_type_manager);

    // Order item with no purchasable entity.
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn(NULL);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    // Order item with a variation belong to product #2.
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->getEntityTypeId()->willReturn('commerce_product_variation');
    $purchased_entity->getProductId()->willReturn(2);
    $purchased_entity = $purchased_entity->reveal();
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn($purchased_entity);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    $configuration['products'][0]['product_id'] = 2;
    $condition->setConfiguration($configuration);
    $this->assertTrue($condition->evaluate($order_item));
  }

}
