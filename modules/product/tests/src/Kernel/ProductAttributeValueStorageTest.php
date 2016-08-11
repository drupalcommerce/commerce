<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the product attribute value storage.
 *
 * @group commerce
 */
class ProductAttributeValueStorageTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'path',
    'text', 'entity', 'filter', 'entity_test', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product', 'views', 'address', 'inline_entity_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
  }

  /**
   * Tests loadByAttribute()
   */
  public function testLoadByAttribute() {
    $color_attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $color_attribute->save();

    ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Black',
      'weight' => 3,
    ])->save();
    ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Yellow',
      'weight' => 2,
    ])->save();
    ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Magenta',
      'weight' => 1,
    ])->save();
    ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Cyan',
      'weight' => 0,
    ])->save();

    /** @var \Drupal\commerce_product\ProductAttributeValueStorageInterface $attribute_value_storage */
    $attribute_value_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product_attribute_value');
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[] $attribute_values */
    $attribute_values = $attribute_value_storage->loadByAttribute('color');

    $value = array_shift($attribute_values);
    $this->assertEquals('Cyan', $value->getName());
    $value = array_shift($attribute_values);
    $this->assertEquals('Magenta', $value->getName());
    $value = array_shift($attribute_values);
    $this->assertEquals('Yellow', $value->getName());
    $value = array_shift($attribute_values);
    $this->assertEquals('Black', $value->getName());
  }

}
