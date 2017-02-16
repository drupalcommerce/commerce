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
   *
   * @return $this
   */
  public function setUnitPrice(Price $unit_price);

  /**
   * Gets the order item total price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The order item total price, or NULL.
   */
  public function getTotalPrice();

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
