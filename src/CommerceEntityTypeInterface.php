<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceEntityTypeInterface.
 */

namespace Drupal\commerce;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface CommerceEntityTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the number of content entities existing with this type.
   *
   * @return int
   */
  public function getContentCount();
}
