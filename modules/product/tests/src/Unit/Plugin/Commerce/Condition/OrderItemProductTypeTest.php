<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemProductType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemProductType
 * @group commerce
 */
class OrderItemProductTypeTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();
    $configuration = [];
    $configuration['product_types'] = ['bag'];
    $condition = new OrderItemProductType($configuration, 'order_item_product_type', ['entity_type' => 'commerce_order_item'], $entity_type_manager);

    // Order item with no purchasable entity.
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn(NULL);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    // Order item with a variation belonging to glass product.
    $product = $this->prophesize(ProductInterface::class);
    $product->bundle()->willReturn('glass');
    $product = $product->reveal();
    $product_variation = $this->prophesize(ProductVariationInterface::class);
    $product_variation->getEntityTypeId()->willReturn('commerce_product_variation');
    $product_variation->getProduct()->willReturn($product);
    $product_variation = $product_variation->reveal();

    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn($product_variation);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    $configuration['product_types'] = ['glass'];
    $condition->setConfiguration($configuration);
    $this->assertTrue($condition->evaluate($order_item));
  }

}
