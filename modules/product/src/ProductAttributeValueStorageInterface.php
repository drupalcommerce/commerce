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
   *   The attribute ID to load attribute values for.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   An array of entity objects indexed by their ids, ordered by weight.
   */
  public function loadByAttribute($attribute_id);

}
