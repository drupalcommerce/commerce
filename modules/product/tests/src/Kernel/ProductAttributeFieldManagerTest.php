<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the attribute field manager.
 *
 * @coversDefaultClass \Drupal\commerce_product\ProductAttributeFieldManager
 *
 * @group commerce
 */
class ProductAttributeFieldManagerTest extends CommerceKernelTestBase {

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');

    $this->attributeFieldManager = $this->container->get('commerce_product.attribute_field_manager');

    $first_variation_type = ProductVariationType::create([
      'id' => 'shirt',
      'label' => 'Shirt',
    ]);
    $first_variation_type->save();
    $second_variation_type = ProductVariationType::create([
      'id' => 'mug',
      'label' => 'Mug',
    ]);
    $second_variation_type->save();
  }

  /**
   * @covers ::getFieldDefinitions
   * @covers ::getFieldMap
   * @covers ::clearCaches
   * @covers ::createField
   * @covers ::canDeleteField
   * @covers ::deleteField
   */
  public function testManager() {
    $color_attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $color_attribute->save();
    $size_attribute = ProductAttribute::create([
      'id' => 'size',
      'label' => 'Size',
    ]);
    $size_attribute->save();

    $this->assertEquals([], $this->attributeFieldManager->getFieldMap('shirt'));
    $this->attributeFieldManager->createField($color_attribute, 'shirt');
    $this->attributeFieldManager->createField($size_attribute, 'shirt');
    $field_map = $this->attributeFieldManager->getFieldMap('shirt');
    $expected_field_map = [
      ['attribute_id' => 'color', 'field_name' => 'attribute_color'],
      ['attribute_id' => 'size', 'field_name' => 'attribute_size'],
    ];
    $this->assertEquals($expected_field_map, $field_map);

    $this->attributeFieldManager->createField($color_attribute, 'mug');
    $this->attributeFieldManager->createField($size_attribute, 'mug');
    $this->attributeFieldManager->deleteField($size_attribute, 'mug');
    $field_map = $this->attributeFieldManager->getFieldMap('mug');
    $expected_field_map = [
      ['attribute_id' => 'color', 'field_name' => 'attribute_color'],
    ];
    $this->assertEquals($expected_field_map, $field_map);

    $field_map = $this->attributeFieldManager->getFieldMap();
    $expected_field_map = [
      'shirt' => [
        ['attribute_id' => 'color', 'field_name' => 'attribute_color'],
        ['attribute_id' => 'size', 'field_name' => 'attribute_size'],
      ],
      'mug' => [
        ['attribute_id' => 'color', 'field_name' => 'attribute_color'],
      ],
    ];
    $this->assertEquals($expected_field_map, $field_map);
  }

}
