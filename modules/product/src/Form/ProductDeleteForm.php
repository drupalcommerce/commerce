<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductDeleteForm
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a product.
 */
class ProductDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Product %label has been deleted.', array('%label' => $this->entity->label())));
    $this->logger('commerce_product')->notice('Product %name has been deleted.', array('%label' => $this->entity->label()));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
