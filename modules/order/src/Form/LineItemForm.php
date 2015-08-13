<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\LineItemForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_line_item entity edit forms.
 */
class LineItemForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The line item %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_line_item.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    return $form;
  }

}
