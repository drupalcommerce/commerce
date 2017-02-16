<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for plugins which act as entity bundles.
 */
interface BundlePluginInterface extends PluginInspectionInterface {

  /**
   * Builds the field definitions for entities of this bundle.
   *
   * Important:
   * Field names must be unique across all bundles.
   * It is recommended to prefix them with the bundle name (plugin ID).
   *
   * @return \Drupal\commerce\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function buildFieldDefinitions();

}
