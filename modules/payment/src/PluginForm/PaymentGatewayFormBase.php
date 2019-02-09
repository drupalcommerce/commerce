<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a base class for payment gateway plugin forms.
 *
 * @see \Drupal\Core\Plugin\PluginFormBase
 */
abstract class PaymentGatewayFormBase extends PluginFormBase implements PaymentGatewayFormInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

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
