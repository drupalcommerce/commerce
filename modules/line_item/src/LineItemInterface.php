<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\LineItemInterface.
 */

namespace Drupal\commerce_line_item;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Commerce Line item entity.
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
   * Gets the line item status.
   *
   * @return string
   *   The line item status.
   */
  public function getStatus();

  /**
   * Sets the line item status.
   *
   * @param string $status
   *   The line item status.
   *
   * @return \Drupal\commerce_line_item\LineItemInterface
   *   The called line item entity.
   */
  public function setStatus($status);

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
   * @return \Drupal\commerce_line_item\LineItemInterface
   *   The called line item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the line item revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the line item revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\commerce_line_item\LineItemInterface
   *   The called line item entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the line item revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionAuthor();

  /**
   * Sets the line item revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\commerce_line_item\LineItemInterface
   *   The called line item entity.
   */
  public function setRevisionAuthorId($uid);

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
   * @return \Drupal\commerce_line_item\LineItemInterface
   *   The called line item entity.
   */
  public function setData($data);

}
