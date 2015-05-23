<?php

/**
 * @file
 * Contains \Drupal\commerce_order\OrderInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce_store\EntityStoreInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Commerce Order entity.
 */
interface OrderInterface extends EntityStoreInterface, EntityChangedInterface, EntityInterface, EntityOwnerInterface {

  /**
   * Gets the order number.
   *
   * @return string
   *   The order number.
   */
  public function getOrderNumber();

  /**
   * Sets the order number.
   *
   * @param string $orderNumber
   *   The order number.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setOrderNumber($orderNumber);

  /**
   * Gets the order type.
   *
   * @return string
   *   The order type.
   */
  public function getType();

  /**
   * Gets the order status.
   *
   * @return string
   *   The order status.
   */
  public function getStatus();

  /**
   * Sets the order status.
   *
   * @param string $status
   *   The order status.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setStatus($status);

  /**
   * Gets the order creation timestamp.
   *
   * @return int
   *   Creation timestamp of the order.
   */
  public function getCreatedTime();

  /**
   * Sets the order creation timestamp.
   *
   * @param int $timestamp
   *   The order creation timestamp.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the order revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the order revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the order revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionAuthor();

  /**
   * Sets the order revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setRevisionAuthorId($uid);

  /**
   * Gets the line items associated with this order.
   *
   * @return array
   *   The line items of this order.
   */
  public function getLineItems();

  /**
   * Sets the line items associated with this order.
   *
   * @param array $lineItems
   *   The line items associated with this order.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setLineItems($lineItems);

  /**
   * Gets the additional data stored in this order.
   *
   * @return array
   *   An array of additional data.
   */
  public function getData();

  /**
   * Sets random information related to this order.
   *
   * @param array $data
   *   An array of additional data.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setData($data);

  /**
   * Gets the IP address that created this order.
   *
   * @return string
   *   The ip address.
   */
  public function getHostname();

  /**
   * Sets the IP address associated with this order.
   *
   * @param string $hostname
   *   The IP address to associate to this order.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setHostname($hostname);

  /**
   * Gets the e-mail address associated with the order.
   *
   * @return string
   *   The order mail.
   */
  public function getEmail();

  /**
   * Sets the order mail.
   *
   * @param string $mail
   *   The e-mail address associated with the order.
   *
   * @return \Drupal\commerce_order\OrderInterface
   *   The called order entity.
   */
  public function setEmail($mail);

}
