<?php
/**
 * @file
 * Definition of Drupal\commerce_order\Form\CommerceOrderForm.
 */
namespace Drupal\commerce_order\Form;
use Drupal\Core\Entity\ContentEntityForm;

/**
 * Form controller for the commerce_order entity edit forms.
 */
class CommerceOrderForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    return $form;
  }

}
