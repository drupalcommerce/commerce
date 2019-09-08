<?php

namespace Drupal\commerce_number_pattern\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

interface NumberPatternInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the target entity type ID.
   *
   * This is the entity type for which the pattern will be used.
   * For example, "commerce_order".
   *
   * @return string
   *   The target entity type ID.
   */
  public function getTargetEntityTypeId();

  /**
   * Sets the target entity type ID.
   *
   * @param string $entity_type_id
   *   The target entity type ID.
   *
   * @return $this
   */
  public function setTargetEntityTypeId($entity_type_id);

  /**
   * Gets the number pattern plugin.
   *
   * @return \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\NumberPatternInterface
   *   The number pattern plugin.
   */
  public function getPlugin();

  /**
   * Gets the number pattern plugin ID.
   *
   * @return string
   *   The number pattern plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the number pattern plugin ID.
   *
   * @param string $plugin_id
   *   The number pattern plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the number pattern plugin configuration.
   *
   * @return array
   *   The number pattern plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the number pattern plugin configuration.
   *
   * @param array $configuration
   *   The number pattern plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

}
