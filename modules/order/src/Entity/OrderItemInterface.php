<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for order items.
 */
interface OrderItemInterface extends ContentEntityInterface, EntityAdjustableInterface, EntityChangedInterface {

  /**
   * Gets the parent order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The order, or NULL.
   */
  public function getOrder();

  /**
   * Gets the parent order ID.
   *
   * @return int|null
   *   The order ID, or NULL.
   */
  public function getOrderId();

  /**
   * Gets whether the order item has a purchased entity.
   *
   * @return bool
   *   TRUE if the order item has a purchased entity, FALSE otherwise.
   */
  public function hasPurchasedEntity();

  /**
   * Gets the purchased entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchased entity, or NULL.
   */
  public function getPurchasedEntity();

  /**
   * Gets the purchased entity ID.
   *
   * @return int
   *   The purchased entity ID.
   */
  public function getPurchasedEntityId();

  /**
   * Gets the order item title.
   *
   * @return string
   *   The order item title
   */
  public function getTitle();

  /**
   * Sets the order item title.
   *
   * @param string $title
   *   The order item title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets the order item quantity.
   *
   * @return string
   *   The order item quantity
   */
  public function getQuantity();

  /**
   * Sets the order item quantity.
   *
   * @param string $quantity
   *   The order item quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

  /**
   * Gets the order item unit price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order item unit price, or NULL.
   */
  public function getUnitPrice();

  /**
   * Sets the order item unit price.
   *
   * @param \Drupal\commerce_price\Price $unit_price
   *   The order item unit price.
   * @param bool $override
   *   Whether the unit price should be overridden.
   *
   * @return $this
   */
  public function setUnitPrice(Price $unit_price, $override = FALSE);

  /**
   * Gets whether the order item unit price is overridden.
   *
   * Overridden unit prices are not updated when the order is refreshed.
   *
   * @return bool
   *   TRUE if the unit price is overridden, FALSE otherwise.
   */
  public function isUnitPriceOverridden();

  /**
   * Gets the order item total price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order item total price, or NULL.
   */
  public function getTotalPrice();

  /**
   * Gets whether the order item uses legacy adjustments.
   *
   * Indicates that the adjustments were calculated based on the unit price,
   * which was the default logic prior to Commerce 2.8, changed in #2980713.
   *
   * @return bool
   *   TRUE if the order item uses legacy adjustments, FALSE otherwise.
   */
  public function usesLegacyAdjustments();

  /**
   * Gets the adjusted order item total price.
   *
   * The adjusted total price is calculated by applying the order item's
   * adjustments to the total price. This can include promotions, taxes, etc.
   *
   * @param string[] $adjustment_types
   *   The adjustment types to include in the adjusted price.
   *   Examples: fee, promotion, tax. Defaults to all adjustment types.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The adjusted order item total price, or NULL.
   */
  public function getAdjustedTotalPrice(array $adjustment_types = []);

  /**
   * Gets the adjusted order item unit price.
   *
   * Calculated by dividing the adjusted total price by quantity.
   *
   * Useful for refunds and other purposes where there's a need to know
   * how much a single unit contributed to the order total.
   *
   * @param string[] $adjustment_types
   *   The adjustment types to include in the adjusted price.
   *   Examples: fee, promotion, tax. Defaults to all adjustment types.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The adjusted order item unit price, or NULL.
   */
  public function getAdjustedUnitPrice(array $adjustment_types = []);

  /**
   * Gets an order item data value with the given key.
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
   * Sets an order item data value with the given key.
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
   * Gets the order item creation timestamp.
   *
   * @return int
   *   The order item creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the order item creation timestamp.
   *
   * @param int $timestamp
   *   The order item creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
