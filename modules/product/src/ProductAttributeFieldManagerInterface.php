<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductAttributeInterface;

/**
 * Manages attribute fields.
 *
 * Attribute fields are entity reference fields storing values of a specific
 * attribute on the product variation.
 */
interface ProductAttributeFieldManagerInterface {

  /**
   * Gets the attribute field definitions.
   *
   * @param string $variation_type_id
   *   The product variation type ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The attribute field definitions, keyed by field name.
   */
  public function getFieldDefinitions($variation_type_id);

  /**
   * Gets a map of attribute fields across variation types.
   *
   * @param string $variation_type_id
   *   (Optional) The product variation type ID.
   *   When given, used to filter the returned maps.
   *
   * @return array
   *   If a product variation type ID was given, a list of maps.
   *   Otherwise, a list of maps grouped by product variation type ID.
   *   Each map is an array with the following keys:
   *   - attribute_id: The attribute id;
   *   - field_name: The attribute field name.
   */
  public function getFieldMap($variation_type_id = NULL);

  /**
   * Clears the attribute field map and definition caches.
   */
  public function clearCaches();

  /**
   * Creates an attribute field for the given attribute.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute
   *   The product attribute.
   * @param string $variation_type_id
   *   The product variation type ID.
   */
  public function createField(ProductAttributeInterface $attribute, $variation_type_id);

  /**
   * Checks whether the attribute field for the given attribute can be deleted.
   *
   * An attribute field is no longer deletable once it has data.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute
   *   The product attribute.
   *
   * @return bool
   *   TRUE if the attribute field can be deleted, FALSE otherwise.
   */
  public function canDeleteField(ProductAttributeInterface $attribute);

  /**
   * Deletes the attribute field for the given attribute.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute
   *   The product attribute.
   * @param string $variation_type_id
   *   The product variation type ID.
   */
  public function deleteField(ProductAttributeInterface $attribute, $variation_type_id);

}
