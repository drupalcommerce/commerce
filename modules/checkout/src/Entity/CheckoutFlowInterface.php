<?php

namespace Drupal\commerce_checkout\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for checkout flows.
 *
 * This configuration entity stores configuration for checkout flow plugins.
 */
interface CheckoutFlowInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the checkout flow plugin.
   *
   * @return \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface
   *   The checkout flow plugin.
   */
  public function getPlugin();

  /**
   * Gets the checkout flow plugin ID.
   *
   * @return string
   *   The checkout flow plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the checkout flow plugin ID.
   *
   * @param string $plugin_id
   *   The checkout flow plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

}
