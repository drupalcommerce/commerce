<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\Form\LineItemDeleteForm.
 */

namespace Drupal\commerce_line_item\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting an order.
 */
class LineItemDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $lineItemType = $this->entity->type->entity->label();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('@type %line_item_label has been deleted.', array('@type' => $lineItemType, '%line_item_label' => $this->entity->label())));
      $this->logger('commerce_order')->notice('@type: deleted %line_item_label.', array('@type' => $this->entity->bundle(), '%line_item_label' => $this->entity->label()));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The order %order_label could not be deleted.', array('%line_item_label' => $this->entity->label())), 'error');
      $this->logger('commerce_order')->error($e);
    }
  }

}
