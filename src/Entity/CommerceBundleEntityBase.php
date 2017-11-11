<?php

namespace Drupal\commerce\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Provides the base class for Commerce bundle entities.
 */
class CommerceBundleEntityBase extends ConfigEntityBundleBase implements CommerceBundleEntityInterface {

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The bundle label.
   *
   * @var string
   */
  protected $label;

  /**
   * The bundle traits.
   *
   * @var array
   */
  protected $traits = [];

  /**
   * {@inheritdoc}
   */
  public function getTraits() {
    return $this->traits;
  }

  /**
   * {@inheritdoc}
   */
  public function setTraits(array $traits) {
    $this->traits = $traits;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTrait($trait) {
    return in_array($trait, $this->traits);
  }

}
