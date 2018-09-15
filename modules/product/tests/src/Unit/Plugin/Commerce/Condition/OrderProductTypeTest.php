<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Commerce\Condition\OrderProductType;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_product\Plugin\Commerce\Condition\OrderProductType
 * @group commerce
 */
class OrderProductTypeTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $configuration = [];
    $configuration['product_types'] = ['bag'];
    $condition = new OrderProductType($configuration, 'order_product_type', ['entity_type' => 'commerce_order']);

    // Order item with no purchasable entity.
    $first_order_item = $this->prophesize(OrderItemInterface::class);
    $first_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $first_order_item->getPurchasedEntity()->willReturn(NULL);
    $first_order_item = $first_order_item->reveal();

    // Order item with a variation belonging to glass product.
    $product = $this->prophesize(ProductInterface::class);
    $product->bundle()->willReturn('glass');
    $product = $product->reveal();
    $product_variation = $this->prophesize(ProductVariationInterface::class);
    $product_variation->getEntityTypeId()->willReturn('commerce_product_variation');
    $product_variation->getProduct()->willReturn($product);
    $product_variation = $product_variation->reveal();
    $second_order_item = $this->prophesize(OrderItemInterface::class);
    $second_order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $second_order_item->getPurchasedEntity()->willReturn($product_variation);
    $second_order_item = $second_order_item->reveal();

    $order = $this->buildOrder([$first_order_item]);
    $this->assertFalse($condition->evaluate($order));

    $order = $this->buildOrder([$second_order_item]);
    $this->assertFalse($condition->evaluate($order));

    $order = $this->buildOrder([$first_order_item, $second_order_item]);
    $this->assertFalse($condition->evaluate($order));

    $configuration['product_types'] = ['glass'];
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
