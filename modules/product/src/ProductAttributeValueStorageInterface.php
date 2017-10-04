<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for product attribute value storage.
 */
interface ProductAttributeValueStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads product attribute values for the given product attribute.
   *
   * @param string $attribute_id
   *   The product attribute ID.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   The product attribute values, indexed by id, ordered by weight.
   */
  public function loadMultipleByAttribute($attribute_id);

}
