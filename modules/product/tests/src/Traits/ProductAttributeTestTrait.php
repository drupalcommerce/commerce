<?php

namespace Drupal\Tests\commerce_product\Traits;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines a trait for attribute-related functional tests.
 */
trait ProductAttributeTestTrait {

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Creates an attribute field and set of attribute values.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   * @param string $name
   *   The attribute field name.
   * @param array $options
   *   Associative array of key name values. [red => Red].
   * @param bool $test_field
   *   Flag to create a test field on the attribute.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   */
  protected function createAttributeSet(ProductVariationTypeInterface $variation_type, $name, array $options, $test_field = FALSE) {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    $this->attributeFieldManager->createField($attribute, $variation_type->id());

    if ($test_field) {
      $field_storage = FieldStorageConfig::loadByName('commerce_product_attribute_value', 'rendered_test');
      if (!$field_storage) {
        $field_storage = FieldStorageConfig::create([
          'field_name' => 'rendered_test',
          'entity_type' => 'commerce_product_attribute_value',
          'type' => 'text',
        ]);
        $field_storage->save();
      }

      FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $attribute->id(),
      ])->save();

      /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $attribute_view_display */
      $attribute_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product_attribute_value',
        'bundle' => $name,
        'mode' => 'add_to_cart',
        'status' => TRUE,
      ]);
      $attribute_view_display->removeComponent('name');
      $attribute_view_display->setComponent('rendered_test', [
        'label' => 'hidden',
        'type' => 'string',
      ]);
      $attribute_view_display->save();
    }

    $attribute_set = [];
    foreach ($options as $key => $value) {
      $attribute_set[$key] = $this->createAttributeValue($name, $value);
    }

    return $attribute_set;
  }

  /**
   * Creates an attribute value.
   *
   * @param string $attribute
   *   The attribute ID.
   * @param string $name
   *   The attribute value name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   The attribute value entity.
   */
  protected function createAttributeValue($attribute, $name) {
    $attribute_value = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();

    return $attribute_value;
  }

}
