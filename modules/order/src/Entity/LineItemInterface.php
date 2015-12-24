<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\LineItemInterface.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for line items.
 */
interface LineItemInterface extends EntityChangedInterface, ContentEntityInterface {

  /**
   * Gets the parent order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The order entity, or null.
   */
  public function getOrder();

  /**
   * Gets the parent order id.
   *
   * @return int|null
   *   The order id, or null.
   */
  public function getOrderId();

  /**
   * Gets the purchased entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchased entity, or null.
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
   * Gets the unit price.
   *
   * @return object
   *   The unit price.
   */
  public function getUnitPrice();

  /**
   * Gets the total price.
   *
   * @return object
   *   The total price.
   */
  public function getTotalPrice();

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
