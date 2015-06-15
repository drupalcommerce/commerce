<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\LineItemTypeInterface.
 */

namespace Drupal\commerce_line_item;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for line item types.
 */
interface LineItemTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the line item type description.
   *
   * @return string
   *   The line item type description.
   */
  public function getDescription();

  /**
   * Sets the description of the line item type.
   *
   * @param string $description
   *   The new description.
   *
   * @return $this
   */
  public function setDescription($description);

}
