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

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);
    $form_state['redirect_route']['route_name'] = 'commerce_order.list';
    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The order %order_label has been successfully saved.', array('%order_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The order %order_label could not be saved.', array('%order_label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_order', $e);
    }
  }

}
