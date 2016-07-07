<?php

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for content type deletion.
 */
class PaymentGatewayDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the plugin ID in the payment gateway storage.
    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($this->entity->getEntityTypeId());
    $storage->setPluginId($this->entity->getPluginId());

    parent::submitForm($form, $form_state);
  }
}
