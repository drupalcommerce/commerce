<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for line items.
 */
interface LineItemInterface extends ContentEntityInterface, EntityAdjustableInterface, EntityChangedInterface {

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
   * Gets the line item title.
   *
   * @return string
   *   The line item title
   */
  public function getTitle();

  /**
   * Sets the line item title.
   *
   * @param string $title
   *   The line item title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets the line item quantity.
   *
   * @return string
   *   The line item quantity
   */
  public function getQuantity();

  /**
   * Sets the line item quantity.
   *
   * @param string $quantity
   *   The line item quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

  /**
   * Gets the line item unit price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The line item unit price, or NULL.
   */
  public function getUnitPrice();

  /**
   * Sets the line item unit price.
   *
   * @param \Drupal\commerce_price\Price $unit_price
   *   The line item unit price.
   *
   * @return $this
   */
  public function setUnitPrice(Price $unit_price);

  /**
   * Gets the line item total price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The line item total price, or NULL.
   */
  public function getTotalPrice();

  /**
   * Gets the line item creation timestamp.
   *
   * @return int
   *   The line item creation timestamp.
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

}
