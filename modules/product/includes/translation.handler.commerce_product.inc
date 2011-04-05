<?php
// $Id$

/**
 * @file
 * Commerce product translation handler for the translation module.
 */

/**
 * Commerce product translation handler.
 *
 * Override the default behaviours to provide the needed commerce product
 * properties.
 *
 * This class is registered in the commerce_product.module using the
 * translation property in hook_entity_info.
 * @see commerce_product.module
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
   * Returns the original language of the commerce product.
   */
  public function getLanguage() {
    return $this->entity->language;
  }

  /**
   * Checks whether the current user has access to this commerce product.
   */
  public function getAccess($op) {
    return commerce_product_access($op, $this->entity);
  }

  /**
   * Returns whether the commerce product is active (TRUE) or disabled (FALSE).
   */
  protected function getStatus() {
    return (boolean) $this->entity->status;
  }
}
