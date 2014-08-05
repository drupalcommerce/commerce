<?php
/**
 * @file
 * Definition of Drupal\commerce_order\Form\CommerceOrderForm.
 */
namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_order entity edit forms.
 */
class CommerceOrderForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The order %order_label has been successfully saved.', array('%order_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The order %order_label could not be saved.', array('%order_label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_order', $e);
    }
    $form_state['redirect_route']['route_name'] = 'entity.commerce_order.list';
  }

}
