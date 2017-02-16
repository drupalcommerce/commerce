<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Installs and uninstalls bundle plugins.
 *
 * Ensures that the fields provided by the bundle plugins are created/deleted.
 */
interface BundlePluginInstallerInterface {

  /**
   * Installs the bundle plugins provided by the specified modules.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param array $modules
   *   The modules.
   */
  public function installBundles(EntityTypeInterface $entity_type, array $modules);

  /**
   * Uninstalls the bundle plugins provided by the specified modules.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param array $modules
   *   The modules.
   */
  public function uninstallBundles(EntityTypeInterface $entity_type, array $modules);

}
