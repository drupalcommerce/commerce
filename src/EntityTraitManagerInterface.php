<?php

namespace Drupal\commerce;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines the interface for commerce_entity_trait plugin managers.
 */
interface EntityTraitManagerInterface extends PluginManagerInterface {

  /**
   * Gets the definitions filtered by entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The definitions.
   */
  public function getDefinitionsByEntityType($entity_type_id);

  /**
   * Detects conflicts between the given trait and the installed traits.
   *
   * A conflict exists if the given trait has a field with a name that's already
   * taken by a field from an installed trait.
   *
   * @param \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface $trait
   *   The trait.
   * @param \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface[] $installed_traits
   *   The installed traits.
   */
  public function detectConflicts(EntityTraitInterface $trait, array $installed_traits);

  /**
   * Installs the given trait for the given entity type and bundle.
   *
   * Installing a trait attaches any fields that the trait provides to the
   * given bundle.
   *
   * @param \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface $trait
   *   The trait.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   */
  public function installTrait(EntityTraitInterface $trait, $entity_type_id, $bundle);

  /**
   * Checks whether the given trait can be uninstalled.
   *
   * A trait can only be uninstalled if the fields it provides contain no data.
   *
   * @param \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface $trait
   *   The trait.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   */
  public function canUninstallTrait(EntityTraitInterface $trait, $entity_type_id, $bundle);

  /**
   * Uninstalls the given trait for the given entity type and bundle.
   *
   * Uninstalling a trait removes any fields that the trait provides from the
   * given bundle.
   *
   * @param \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface $trait
   *   The trait.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   */
  public function uninstallTrait(EntityTraitInterface $trait, $entity_type_id, $bundle);

}
