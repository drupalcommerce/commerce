<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityHandlerInterface;

/**
 * Handles plugin-provided bundles.
 */
interface BundlePluginHandlerInterface extends EntityHandlerInterface {

  /**
   * Gets the bundle info.
   *
   * @return array
   *   An array of bundle information keyed by the bundle name.
   *   The format expected by hook_entity_bundle_info().
   */
  public function getBundleInfo();

  /**
   * Gets the field storage definitions.
   */
  public function getFieldStorageDefinitions();

  /**
   * Gets the field definitions for a specific bundle.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return \Drupal\commerce\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function getFieldDefinitions($bundle);

}
