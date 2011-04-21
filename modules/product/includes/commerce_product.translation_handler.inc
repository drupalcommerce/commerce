<?php

/**
 * @file
 * Translation handler for the Commerce Product entity.
 */

/**
 * Commerce Product translation handler.
 *
 * This class is registered in commerce_product.module using the translation
 * property the Entity Translation module uses via hook_entity_info().
 *
 * @see commerce_product_entity_info()
 */
class EntityTranslationCommerceProductHandler extends EntityTranslationDefaultHandler {

  public function __construct($entity_type, $entity_info, $entity, $entity_id) {
    parent::__construct('commerce_product', $entity_info, $entity, $entity_id);
  }

  /**
   * Indicates whether this commerce product is a revision or not.
   */
  public function isRevision() {
    return !empty($this->entity->revision);
  }

  /**
   * Returns the original language of the product.
   */
  public function getLanguage() {
    return $this->entity->language;
  }

  /**
   * Checks whether the current user has access to this product.
   */
  public function getAccess($op) {
    return commerce_product_access($op, $this->entity);
  }

  /**
   * Returns whether the product is active (TRUE) or disabled (FALSE).
   */
  protected function getStatus() {
    return (boolean) $this->entity->status;
  }
}
