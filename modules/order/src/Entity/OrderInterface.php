<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\OrderInterface.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_store\Entity\EntityStoreInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for orders.
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
   * @return $this
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
   * @return $this
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
   * @return $this
   */
  public function setCreatedTime($timestamp);

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
   * @return $this
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
   * @return $this
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
   * @return $this
   */
  public function setHostname($hostname);

  /**
   * Gets the email address associated with the order.
   *
   * @return string
   *   The order mail.
   */
  public function getEmail();

  /**
   * Sets the order mail.
   *
   * @param string $mail
   *   The email address associated with the order.
   *
   * @return $this
   */
  public function setEmail($mail);

}
