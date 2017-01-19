<?php

namespace Drupal\commerce\Plugin\Commerce\EntityTrait;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the base entity trait class.
 */
abstract class EntityTraitBase extends PluginBase implements EntityTraitInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeIds() {
    return $this->pluginDefinition['entity_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Entity traits are not required to provide fields.
  }

}
