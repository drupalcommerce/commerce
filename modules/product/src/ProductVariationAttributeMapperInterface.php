<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Provides logic for selecting variations using attributes.
 *
 * @see \Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationAttributesWidget
 */
interface ProductVariationAttributeMapperInterface {

  /**
   * Selects the best matching variation for the given attribute values.
   *
   * Takes the first variation that matches the most attribute values.
   * Partial matches are considered when a full match cannot be made.
   * For example, when given [Red, Small, Cotton], the search priority is:
   * 1) [Red, Small, Cotton]
   * 2) [Red, Small]
   * 3) [Red]
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   * @param array $attribute_values
   *   Attribute value IDs, keyed by the field name.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface|null
   *   The selected variation, or NULL if none could be selected.
   */
  public function selectVariation(array $variations, array $attribute_values = []);

  /**
   * Prepares the available attributes for the selected product variation.
   *
   * The attribute and its values are retrieved in the language of the
   * variation. Values not corresponding to any variation are removed.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation
   *   The selected product variation.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The available product variations.
   *
   * @return \Drupal\commerce_product\PreparedAttribute[]
   *   The prepared attributes, keyed by field name.
   */
  public function prepareAttributes(ProductVariationInterface $selected_variation, array $variations);

}
