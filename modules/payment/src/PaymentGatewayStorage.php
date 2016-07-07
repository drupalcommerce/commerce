<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for payment gateway configuration entities.
 */
class PaymentGatewayStorage extends ConfigEntityStorage implements PaymentGatewayStorageInterface {

  /**
   * The payment gateway plugin, memory storage.
   *
   * This value is not stored in the backend. It's used during the deletion of
   * a payment gateway to save the plugin ID in the same request. The value is
   * used later, when deleting bundle field definitions.
   *
   * @var string[]
   *
   * @see \Drupal\commerce_payment\Form\PaymentGatewayDeleteForm::submitForm()
   */
  protected $plugin = NULL;

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId($entity_id) {
    return $this->plugin;
  }
}
