<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Provides mapping between variations and attributes.
 *
 * This is used when working with variations and their attributes, such as
 * the 'commerce_product_variation_attributes' widget.
 */
interface ProductVariationAttributeMapperInterface {

  /**
   * Gets the variation that best matches the provided the attributes.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   * @param array $attribute_values
   *   An array of attribute values, keyed by the attribute name.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The variation.
   */
  public function getVariation(array $variations, array $attribute_values = []);

  /**
   * Gets the attribute information for the selected product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation
   *   The selected product variation.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The available product variations.
   *
   * @return array[]
   *   The attribute information, keyed by field name.
   */
  public function getAttributeInfo(ProductVariationInterface $selected_variation, array $variations);

  /**
   * Gets the attribute values of a given set of variations.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   * @param string $field_name
   *   The field name of the attribute.
   * @param callable|null $callback
   *   An optional callback to use for filtering the list.
   *
   * @return array[]
   *   The attribute values, keyed by attribute ID.
   */
  public function getAttributeValues(array $variations, $field_name, callable $callback = NULL);

}
