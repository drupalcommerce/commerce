<?php

/**
 * @file
 * Contains \Drupal\example\Form\ExampleDeleteForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Builds the form to delete an order type.
 */
class CommerceOrderTypeDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_order_type.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    try {
      $this->entity->delete();
      $form_state['redirect_route'] = $this->getCancelUrl();
      drupal_set_message($this->t('Order type %label has been deleted.', array('%label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Order type %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_order', $e);
    }
  }
}
