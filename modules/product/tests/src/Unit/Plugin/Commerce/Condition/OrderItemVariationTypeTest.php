<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemVariationType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemVariationType
 * @group commerce
 */
class OrderItemVariationTypeTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();
    $configuration = [];
    $configuration['variation_types'] = ['bag'];
    $condition = new OrderItemVariationType($configuration, 'order_item_variation_type', ['entity_type' => 'commerce_order_item'], $entity_type_manager);

    // Order item with no purchasable entity.
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn(NULL);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    // Order item with a glass variation.
    $product_variation = $this->prophesize(ProductVariationInterface::class);
    $product_variation->getEntityTypeId()->willReturn('commerce_product_variation');
    $product_variation->bundle()->willReturn('glass');
    $product_variation = $product_variation->reveal();
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn($product_variation);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    $configuration['variation_types'] = ['glass'];
    $condition->setConfiguration($configuration);
    $this->assertTrue($condition->evaluate($order_item));
  }

}
