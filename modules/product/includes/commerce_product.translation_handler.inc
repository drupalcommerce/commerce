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

  public function __construct($entity_type, $entity_info, $entity) {
    parent::__construct('commerce_product', $entity_info, $entity);
  }

  /**
   * Indicates whether this commerce product is a revision or not.
   */
  public function isRevision() {
    return !empty($this->entity->revision);
  }

  /**
   * Checks whether the current user has access to this product.
   */
  public function getAccess($op) {
    return commerce_product_access($op, $this->entity);
  }

  /**
   * Tweaks the product form to support multilingual elements.
   */
  public function entityForm(&$form, &$form_state) {
    parent::entityForm($form, $form_state);
    if (isset($form['change_history']['#weight'])) {
      $form['translation']['#weight'] = $form['change_history']['#weight'] - 0.01;
    }
    $form['actions']['delete_translation']['#suffix'] = $form['actions']['submit']['#suffix'];
    unset($form['actions']['submit']['#suffix']);
  }

  /**
   * @see EntityTranslationDefaultHandler::entityFormTitle()
   */
  protected function entityFormTitle() {
    return commerce_product_ui_product_title($this->entity);
  }

  /**
   * Returns whether the product is active (TRUE) or disabled (FALSE).
   */
  protected function getStatus() {
    return (boolean) $this->entity->status;
  }
}
