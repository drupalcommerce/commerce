<?php

namespace Drupal\Tests\commerce_product\Unit\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
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

    $uuid_map = [
      '2' => '62e428e1-88a6-478c-a8c6-a554ca2332ae',
      '3' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      '4' => 'a019d89b-c4d9-4ed4-b859-894e4e2e93cf',
    ];
    $entity_uuid_mapper = $this->prophesize(EntityUuidMapperInterface::class);
    $entity_uuid_mapper->mapToIds('taxonomy_term', [$uuid_map['3']])->willReturn(['3']);
    $entity_uuid_mapper->mapToIds('taxonomy_term', [$uuid_map['4']])->willReturn(['4']);
    $entity_uuid_mapper = $entity_uuid_mapper->reveal();

    $configuration = [];
    $configuration['terms'] = ['30df59bd-7b03-4cf7-bb35-d42fc49f0651'];
    $condition = new OrderItemProductCategory($configuration, 'order_item_product_category', ['entity_type' => 'commerce_order_item'], $entity_field_manager, $entity_type_manager, $entity_uuid_mapper);

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
    $product->hasField('field_product_category')->willReturn(TRUE);
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
    $configuration['terms'] = ['a019d89b-c4d9-4ed4-b859-894e4e2e93cf'];
    $condition->setConfiguration($configuration);
    $this->assertTrue($condition->evaluate($order_item));
  }

}
