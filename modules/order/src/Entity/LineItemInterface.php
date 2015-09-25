<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\LineItemInterface.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for line items.
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
   * Sets the purchased entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchased entity.
   *
   * @return $this
   */
  public function setPurchasedEntity(PurchasableEntityInterface $entity);

  /**
   * Gets the purchased entity ID.
   *
   * @return int
   *   The purchased entity ID.
   */
  public function getPurchasedEntityId();

  /**
   * Sets the purchased entity ID.
   *
   * @param int $entityId
   *   The purchased entity ID.
   *
   * @return $this
   */
  public function setPurchasedEntityId($entityId);

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
