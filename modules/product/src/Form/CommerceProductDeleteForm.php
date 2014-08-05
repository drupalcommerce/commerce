<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceProductDeleteForm
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a product.
 */
class CommerceProductDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the product %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('commerce_product.list');
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Product %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('commerce', 'Product %name has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect_route']['route_name'] = 'commerce_product.list';
  }
}
