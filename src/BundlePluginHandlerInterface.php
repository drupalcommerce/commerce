<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

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
   *
   * @return \Drupal\commerce\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function getFieldStorageDefinitions(EntityTypeInterface $entity_type);

  /**
   * Gets the field definitions.
   *
   * @return \Drupal\commerce\BundleFieldDefinition[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function getFieldDefinitions(EntityTypeInterface $entity_type, $bundle);

}
