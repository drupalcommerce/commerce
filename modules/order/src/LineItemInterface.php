<?php

/**
 * @file
 * Contains \Drupal\commerce_order\LineItemInterface.
 */

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for line items.
 */
interface LineItemInterface extends EntityChangedInterface, EntityInterface, EntityOwnerInterface {

  /**
   * Gets the line item type.
   *
   * @return string
   *   The line item type.
   */
  public function getType();

  /**
   * Gets the line item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the line item.
   */
  public function getCreatedTime();

  /**
   * Sets the line item creation timestamp.
   *
   * @param int $timestamp
   *   The line item creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the additional data stored in this line item.
   *
   * @return array
   *   An array of additional data.
   */
  public function getData();

  /**
   * Sets random information related to this line item.
   *
   * @param array $data
   *   An array of additional data.
   *
   * @return $this
   */
  public function setData($data);

}
