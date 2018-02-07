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
   * Whether the bundle is locked, indicating that it cannot be deleted.
   *
   * @var bool
   */
  protected $locked = FALSE;

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

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function lock() {
    $this->locked = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    $this->locked = FALSE;
    return $this;
  }

}
