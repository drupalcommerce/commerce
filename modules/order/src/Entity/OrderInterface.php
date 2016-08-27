<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_store\Entity\EntityStoreInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the interface for orders.
 */
interface OrderInterface extends ContentEntityInterface, EntityAdjustableInterface, EntityChangedInterface, EntityOwnerInterface, EntityStoreInterface {

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
   * Gets the order email.
   *
   * @return string
   *   The order email.
   */
  public function getEmail();

  /**
   * Sets the order email.
   *
   * @param string $mail
   *   The order email.
   *
   * @return $this
   */
  public function setEmail($mail);

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
   * Gets the billing profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The billing profile.
   */
  public function getBillingProfile();

  /**
   * Sets the billing profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The billing profile.
   *
   * @return $this
   */
  public function setBillingProfile(ProfileInterface $profile);

  /**
   * Gets the billing profile ID.
   *
   * @return int
   *   The billing profile ID.
   */
  public function getBillingProfileId();

  /**
   * Sets the billing profile ID.
   *
   * @param int $billingProfileId
   *   The billing profile ID.
   *
   * @return $this
   */
  public function setBillingProfileId($billingProfileId);

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
   * Gets the order total price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order total price, or NULL.
   */
  public function getTotalPrice();

  /**
   * Gets the order state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The order state.
   */
  public function getState();

  /**
   * Gets the order data.
   *
   * Used to store temporary data during order processing (i.e. checkout).
   *
   * @return array
   *   The order data.
   */
  public function getData();

  /**
   * Sets the order data.
   *
   * @param array $data
   *   The order data.
   *
   * @return $this
   */
  public function setData($data);

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
   * Gets the order placed timestamp.
   *
   * @return int
   *   The order placed timestamp.
   */
  public function getPlacedTime();

  /**
   * Sets the order placed timestamp.
   *
   * @param int $timestamp
   *   The order placed timestamp.
   *
   * @return $this
   */
  public function setPlacedTime($timestamp);

}
