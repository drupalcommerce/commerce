<?php

namespace Drupal\commerce_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for tax types.
 *
 * This configuration entity stores configuration for tax type plugins.
 */
interface TaxTypeInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the tax type plugin.
   *
   * @return \Drupal\commerce_tax\Plugin\Commerce\TaxType\TaxTypeInterface
   *   The tax type plugin.
   */
  public function getPlugin();

  /**
   * Gets the tax type plugin ID.
   *
   * @return string
   *   The tax type plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the tax type plugin ID.
   *
   * @param string $plugin_id
   *   The tax type plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the tax type plugin configuration.
   *
   * @return array
   *   The tax type plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the tax type plugin configuration.
   *
   * @param array $configuration
   *   The tax type plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

}
