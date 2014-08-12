<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceProductForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the product edit form.
 */
class CommerceProductForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_product\Entity\CommerceProduct */
    $form = parent::form($form, $form_state);
    $product = $this->entity;

    // There appears to be no way to set default value for checkboxes in the
    // field definitions yet ...
    if ($product->isNew()) {
      $form['status']['widget']['#default_value'] = 1;
    }

    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);
    $form_state->setRedirect('commerce_product.list');
    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The product %product_label has been successfully saved.', array('%product_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The product %product_label could not be saved.', array('%product_label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_product', $e);
    }
  }

}
