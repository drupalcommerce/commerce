<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderDeleteForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting an order.
 */
class OrderDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the order %order_label?', array('%order_label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_order.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $orderType = $this->entity->type->entity->label();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('@type %order_label has been deleted.', array('@type' => $orderType, '%order_label' => $this->entity->label())));
      $this->logger('commerce_order')->notice('@type: deleted %order_label.', array('@type' => $this->entity->bundle(), '%order_label' => $this->entity->label()));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The order %order_label could not be deleted.', array('%order_label' => $this->entity->label())), 'error');
      $this->logger('commerce_order')->error($e);
    }
  }

}
