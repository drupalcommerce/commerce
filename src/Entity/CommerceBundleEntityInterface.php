<?php

namespace Drupal\commerce\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides the interface for Commerce bundle entities.
 *
 * Each bundle entity can have traits attached.
 *
 * @see \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface
 */
interface CommerceBundleEntityInterface extends ConfigEntityInterface {

  /**
   * Gets the traits.
   *
   * @return array
   *   The trait plugin IDs.
   */
  public function getTraits();

  /**
   * Sets the traits.
   *
   * @param array $traits
   *   The trait plugin IDs.
   *
   * @return $this
   */
  public function setTraits(array $traits);

  /**
   * Gets whether the bundle has the given trait.
   *
   * @param string $trait
   *   The trait plugin ID.
   */
  public function hasTrait($trait);

}
