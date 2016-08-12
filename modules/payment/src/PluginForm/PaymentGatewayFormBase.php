<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce\PluginForm\PluginFormBase;
use Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for payment gateway plugin forms.
 *
 * @see \Drupal\commerce\PluginForm\PluginFormBase
 */
abstract class PaymentGatewayFormBase extends PluginFormBase implements PaymentGatewayFormInterface {

  /**
   * The form entity.
   *
   * @var \Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityWithPaymentGatewayInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorElement(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
