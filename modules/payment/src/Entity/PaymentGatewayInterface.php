<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for payment gateway configuration entities.
 *
 * Stores configuration for payment gateway plugins.
 */
interface PaymentGatewayInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the payment gateway weight.
   *
   * @return string
   *   The payment gateway weight.
   */
  public function getWeight();

  /**
   * Sets the payment gateway weight.
   *
   * @param int $weight
   *   The payment gateway weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the payment gateway plugin.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface
   *   The payment gateway plugin.
   */
  public function getPlugin();

  /**
   * Gets the payment gateway plugin ID.
   *
   * @return string
   *   The payment gateway plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the payment gateway plugin ID.
   *
   * @param string $plugin_id
   *   The payment gateway plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

}
