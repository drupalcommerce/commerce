<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\EntityStoreInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Defines the interface for orders.
 */
interface OrderInterface extends ContentEntityInterface, EntityAdjustableInterface, EntityChangedInterface, EntityStoreInterface {

  // Refresh states.
  const REFRESH_ON_LOAD = 'refresh_on_load';
  const REFRESH_ON_SAVE = 'refresh_on_save';
  const REFRESH_SKIP = 'refresh_skip';

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
   * Gets the customer user.
   *
   * @return \Drupal\user\UserInterface
   *   The customer user entity. If the order is anonymous (customer
   *   unspecified or deleted), an anonymous user will be returned. Use
   *   $customer->isAnonymous() to check.
   */
  public function getCustomer();

  /**
   * Sets the customer user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The customer user entity.
   *
   * @return $this
   */
  public function setCustomer(UserInterface $account);

  /**
   * Gets the customer user ID.
   *
   * @return int
   *   The customer user ID ('0' if anonymous).
   */
  public function getCustomerId();

  /**
   * Sets the customer user ID.
   *
   * @param int $uid
   *   The customer user ID.
   *
   * @return $this
   */
  public function setCustomerId($uid);

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
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The billing profile, or NULL if none found.
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
   * Collects all profiles that belong to the order.
   *
   * This includes the billing profile, plus other profiles added
   * by modules through event subscribers (e.g. the shipping profile).
   *
   * @return \Drupal\profile\Entity\ProfileInterface[]
   *   The order's profiles, keyed by scope (billing, shipping, etc).
   */
  public function collectProfiles();

  /**
   * Gets the order items.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   The order items.
   */
  public function getItems();

  /**
   * Sets the order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The order items.
   *
   * @return $this
   */
  public function setItems(array $order_items);

  /**
   * Gets whether the order has order items.
   *
   * @return bool
   *   TRUE if the order has order items, FALSE otherwise.
   */
  public function hasItems();

  /**
   * Adds an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return $this
   */
  public function addItem(OrderItemInterface $order_item);

  /**
   * Removes an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return $this
   */
  public function removeItem(OrderItemInterface $order_item);

  /**
   * Checks whether the order has a given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return bool
   *   TRUE if the order item was found, FALSE otherwise.
   */
  public function hasItem(OrderItemInterface $order_item);

  /**
   * Removes all adjustments that belong to the order.
   *
   * This includes both order and order item adjustments, with the exception
   * of adjustments added via the UI.
   *
   * @return $this
   */
  public function clearAdjustments();

  /**
   * Collects all adjustments that belong to the order.
   *
   * Unlike getAdjustments() which returns only order adjustments, this
   * method returns both order and order item adjustments.
   *
   * Important:
   * The returned adjustments are unprocessed, and must be processed before use.
   *
   * @param string[] $adjustment_types
   *   The adjustment types to include.
   *   Examples: fee, promotion, tax. Defaults to all adjustment types.
   *
   * @return \Drupal\commerce_order\Adjustment[]
   *   The adjustments.
   *
   * @see \Drupal\commerce_order\AdjustmentTransformerInterface::processAdjustments()
   */
  public function collectAdjustments(array $adjustment_types = []);

  /**
   * Gets the order subtotal price.
   *
   * Represents a sum of all order item totals.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order subtotal price, or NULL.
   */
  public function getSubtotalPrice();

  /**
   * Recalculates the order total price.
   *
   * @return $this
   */
  public function recalculateTotalPrice();

  /**
   * Gets the order total price.
   *
   * Represents a sum of all order item totals along with adjustments.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order total price, or NULL.
   */
  public function getTotalPrice();

  /**
   * Gets the total paid price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The total paid price, or NULL.
   */
  public function getTotalPaid();

  /**
   * Sets the total paid price.
   *
   * @param \Drupal\commerce_price\Price $total_paid
   *   The total paid price.
   */
  public function setTotalPaid(Price $total_paid);

  /**
   * Gets the order balance.
   *
   * Calculated by subtracting the total paid price from the total price.
   * Can be negative in case the order was overpaid.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order balance, or NULL.
   */
  public function getBalance();

  /**
   * Gets whether the order has been fully paid.
   *
   * Free orders (total price is zero) are considered fully paid once
   * they have been placed.
   *
   * Non-free orders are considered fully paid once their balance
   * becomes zero or negative.
   *
   * @return bool
   *   TRUE if the order has been fully paid, FALSE otherwise.
   */
  public function isPaid();

  /**
   * Gets the order state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The order state.
   */
  public function getState();

  /**
   * Gets the order refresh state.
   *
   * @return string|null
   *   The refresh state, if set. One of the following order constants:
   *   REFRESH_ON_LOAD: The order should be refreshed when it is next loaded.
   *   REFRESH_ON_SAVE: The order should be refreshed before it is saved.
   *   REFRESH_SKIP: The order should not be refreshed for now.
   */
  public function getRefreshState();

  /**
   * Sets the order refresh state.
   *
   * @param string $refresh_state
   *   The order refresh state.
   *
   * @return $this
   */
  public function setRefreshState($refresh_state);

  /**
   * Gets an order data value with the given key.
   *
   * Used to store temporary data during order processing (i.e. checkout).
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key, $default = NULL);

  /**
   * Sets an order data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

  /**
   * Unsets an order data value with the given key.
   *
   * @param string $key
   *   The key.
   *
   * @return $this
   */
  public function unsetData($key);

  /**
   * Gets whether the order is locked.
   *
   * @return bool
   *   TRUE if the order is locked, FALSE otherwise.
   */
  public function isLocked();

  /**
   * Locks the order.
   *
   * @return $this
   */
  public function lock();

  /**
   * Unlocks the order.
   *
   * @return $this
   */
  public function unlock();

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

  /**
   * Gets the order completed timestamp.
   *
   * @return int
   *   The order completed timestamp.
   */
  public function getCompletedTime();

  /**
   * Sets the order completed timestamp.
   *
   * @param int $timestamp
   *   The order completed timestamp.
   *
   * @return $this
   */
  public function setCompletedTime($timestamp);

  /**
   * Gets the calculation date/time for the order.
   *
   * Used during order processing, for determining promotion/tax availability.
   * Matches the placed timestamp, if the order has been placed.
   * Otherwise, falls back to the current request date/time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The calculation date/time, in the store timezone.
   */
  public function getCalculationDate();

}
