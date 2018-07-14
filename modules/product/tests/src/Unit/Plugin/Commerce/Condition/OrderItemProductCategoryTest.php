<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemProductCategory;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_product\Plugin\Commerce\Condition\OrderItemProductCategory
 * @group commerce
 */
class OrderItemProductCategoryTest extends UnitTestCase {

  /**
   * ::covers evaluate.
   */
  public function testEvaluate() {
    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $entity_field_manager->getFieldMapByFieldType('entity_reference')->willReturn([
      'commerce_product' => [
        'field_product_category' => [
          'bundles' => ['default'],
        ],
      ],
    ]);
    $entity_field_manager = $entity_field_manager->reveal();

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();
    $configuration = [];
    $configuration['terms'] = [3];
    $condition = new OrderItemProductCategory($configuration, 'order_item_product_category', ['entity_type' => 'commerce_order_item'], $entity_field_manager, $entity_type_manager);

    // Order item with no purchasable entity.
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn(NULL);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    // Product with a non-matching product category.
    $entity_reference_item_list = $this->prophesize(EntityReferenceFieldItemList::class);
    $entity_reference_item_list->isEmpty()->willReturn(FALSE);
    $entity_reference_item_list->getValue()->willReturn([
      '1' => [
        'target_id' => '2',
      ],
      '2' => [
        'target_id' => '4',
      ],
    ]);
    $entity_reference_item_list = $entity_reference_item_list->reveal();
    $product = $this->prophesize(ProductInterface::class);
    $product->get('field_product_category')->willReturn($entity_reference_item_list);
    $product = $product->reveal();
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->getEntityTypeId()->willReturn('commerce_product_variation');
    $purchased_entity->getProduct()->willReturn($product);
    $purchased_entity = $purchased_entity->reveal();
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getEntityTypeId()->willReturn('commerce_order_item');
    $order_item->getPurchasedEntity()->willReturn($purchased_entity);
    $order_item = $order_item->reveal();
    $this->assertFalse($condition->evaluate($order_item));

    // Matching product category.
    $configuration['terms'] = ['4'];
    $condition->setConfiguration($configuration);
    $this->assertTrue($condition->evaluate($order_item));
  }

}
