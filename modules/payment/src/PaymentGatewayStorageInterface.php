<?php

namespace Drupal\commerce_payment;

/**
 * Interface for storage controllers for payment gateway configuration entities
 */
interface PaymentGatewayStorageInterface {

  /**
   * Stores a plugin ID for a payment gateway being deleted.
   *
   * @param string $plugin
   *   The ID of the plugin used by the payment gateway
   */
  public function setPluginId($plugin);

  /**
   * Retrieves the plugin ID of a deleted payment gateway.
   *
   * @return string|null
   *   The ID of the plugin that was used by the payment gateway, or NULL.
   */
  public function getPluginId();

}
