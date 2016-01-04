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
use Drupal\profile\Entity\ProfileInterface;

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
   * @param string $order_number
   *   The order number.
   *
   * @return $this
   */
  public function setOrderNumber($order_number);

  /**
   * Gets the order state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The order state.
   */
  public function getState();

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
   * Gets the line items.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface[]
   *   The line items.
   */
  public function getLineItems();

  /**
   * Sets the line items.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface[] $line_items
   *   The line items.
   *
   * @return $this
   */
  public function setLineItems(array $line_items);

  /**
   * Gets whether the order has line items.
   *
   * @return bool
   *   TRUE if the order has line items, FALSE otherwise.
   */
  public function hasLineItems();

  /**
   * Adds a line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   *
   * @return $this
   */
  public function addLineItem(LineItemInterface $line_item);

  /**
   * Removes a line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   *
   * @return $this
   */
  public function removeLineItem(LineItemInterface $line_item);

  /**
   * Checks whether the order has a given line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   *
   * @return bool
   *   TRUE if the line item was found, FALSE otherwise.
   */
  public function hasLineItem(LineItemInterface $line_item);

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
   * Gets the order IP address.
   *
   * @return string
   *   The IP address.
   */
  public function getIpAddress();

  /**
   * Sets the order IP address.
   *
   * @param string $ip_address
   *   The IP address.
   *
   * @return $this
   */
  public function setIpAddress($ip_address);

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

  /**
   * Gets the timestamp of when the order was placed.
   *
   * @return int
   *   The timestamp of when the order was placed.
   */
  public function getPlacedTime();

  /**
   * Sets the timestamp of when the order was placed.
   *
   * @param int $timestamp
   *   The timestamp of when the order was placed.
   *
   * @return $this
   */
  public function setPlacedTime($timestamp);

  /**
   * Gets the billing profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The billing profile entity.
   */
  public function getBillingProfile();

  /**
   * Sets the billing profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The billing profile entity.
   *
   * @return $this
   */
  public function setBillingProfile(ProfileInterface $profile);

  /**
   * Gets the billing profile id.
   *
   * @return int
   *   The billing profile id.
   */
  public function getBillingProfileId();

  /**
   * Sets the billing profile id.
   *
   * @param int $billingProfileId
   *   The billing profile id.
   *
   * @return $this
   */
  public function setBillingProfileId($billingProfileId);

}
