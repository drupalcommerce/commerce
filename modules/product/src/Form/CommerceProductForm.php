<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceProductForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;

/**
 * Form controller for the product edit form.
 */
class CommerceProductForm extends ContentEntityForm {
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  // public function form(array $form, array &$form_state) {
    /* @var $entity \Drupal\commerce\Entity\CommerceProduct */
    /*$form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['type'] = array(
      '#type' => 'hidden',
      '#default_value' => $entity->getEntityTypeId(),
    );
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Product title'),
      '#default_value' => $entity->getName(),
      '#required' => TRUE,
    );
    $form['sku'] = array(
      '#type' => 'textfield',
      '#title' => t('Product SKU'),
      '#default_value' => $entity->getSku(),
      '#required' => TRUE,
    );

    return $form;
  }*/

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  
  public function submit(array $form, array &$form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);
    $form_state['redirect_route']['route_name'] = 'commerce_product.list';
    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $entity->save();
  }
}
