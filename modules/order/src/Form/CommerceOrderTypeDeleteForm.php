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
  public function getCancelRoute() {
    return new Url('commerce_order.type_list');
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
    $this->entity->delete();
    drupal_set_message($this->t('Order type %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }
}
