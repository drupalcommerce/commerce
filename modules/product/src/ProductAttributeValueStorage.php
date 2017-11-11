<?php

namespace Drupal\commerce_product;

use Drupal\commerce\CommerceContentEntityStorage;

/**
 * Defines the product attribute value storage.
 */
class ProductAttributeValueStorage extends CommerceContentEntityStorage implements ProductAttributeValueStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByAttribute($attribute_id) {
    $entity_query = $this->getQuery();
    $entity_query->condition('attribute', $attribute_id);
    $entity_query->sort('weight');
    $entity_query->sort('name');
    $result = $entity_query->execute();
    return $result ? $this->loadMultiple($result) : [];
  }

}
