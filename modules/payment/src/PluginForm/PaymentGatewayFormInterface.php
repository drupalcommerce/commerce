<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for payment gateway plugin forms.
 */
interface PaymentGatewayFormInterface extends PluginFormInterface {

  /**
   * Gets the form entity.
   *
   * Allows the parent form to get the updated form entity after submitForm()
   * performs the final changes.
   *
   * @return \Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface
   *   The form entity.
   */
  public function getEntity();

  /**
   * Sets the form entity.
   *
   * @param \Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface $entity
   *   The form entity.
   *
   * @return $this
   */
  public function setEntity(EntityWithPaymentGatewayInterface $entity);

  /**
   * Gets the form element to which errors should be assigned.
   *
   * @param array $form
   *   The form, as built by buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element.
   */
  public function getErrorElement(array $form, FormStateInterface $form_state);

}
