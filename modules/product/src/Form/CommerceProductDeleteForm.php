<?php

/**
 * @file
 * Contains Drupal\commerce\Form\CommerceProductDeleteForm
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Product %label has been deleted.', array('%label' => $this->entity->label())));
    $this->logger('commerce_product')->notice('Product %name has been deleted.', array('%label' => $this->entity->label()));
    $form_state->setRedirect('commerce_product.list');
  }

}
