<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides methods for working with products, variations, and attributes.
 *
 * This trait is meant to be used only by test classes.
 *
 * @todo Move to \Drupal\Tests\commerce_product post-SimpleTest.
 */
trait ProductTestTrait {

  /**
   * Creates a set of product variations.
   *
   * The $values_matrix parameter contains the price and attributes information.
   *
   * Example:
   * [
   *   [
   *      'price' => 9.99
   *      'attribute_color' => 2,
   *      'attribute_size' => 10,
   *   ],
   *   [
   *      'price' => 9.99
   *      'attribute_color' => 2,
   *      'attribute_size' => 6,
   *   ],
   * ]
   *
   * @param string $type
   *   The variation type.
   * @param array $values_matrix
   *   Array of values.
   * @param string $currency_code
   *   The currency code.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   Array of created variations
   */
  protected function createProductVariations($type = 'default', $values_matrix = [], $currency_code = 'USD') {
    $variations = [];
    foreach ($values_matrix as $item) {
      $variation_values = [
        'type' => $type,
        'sku' => strtolower($this->randomMachineName()),
        'price' => [
          'currency_code' => $currency_code,
        ],
      ];

      foreach ($item as $key => $value) {
        if ($key == 'price') {
          $variation_values['price']['amount'] = $value;
        }
        else {
          $variation_values[$key] = $value;
        }
      }

      $variation = ProductVariation::create($variation_values);
      $variation->save();
      $variations[] = ProductVariation::load($variation->id());
    }

    return $variations;
  }

  /**
   * Creates an attribute field and set of attribute values.
   *
   * @param string $variation_type
   *   The variation type.
   * @param string $name
   *   The attribute field name.
   * @param array $values
   *   Associative array of key name values. [red => Red].
   * @param bool $test_field
   *   Flag to create a test field on the attribute.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   */
  protected function createAttributeSet($variation_type, $name, array $values, $test_field = FALSE) {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    \Drupal::service('commerce_product.attribute_field_manager')->createField($attribute, $variation_type);

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
    foreach ($values as $key => $value) {
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
    $attribute_value = ProductAttributeValue::create([
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();
    $attribute_value = ProductAttributeValue::load($attribute_value->id());

    return $attribute_value;
  }

}
