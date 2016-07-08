<?php

namespace Drupal\commerce_discount\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for discounts.
 */
interface DiscountInterface extends EntityInterface {

  /**
   * Gets the discount name.
   *
   * @return string
   *   The discount name.
   */
  public function getName();

  /**
   * Sets the discount name.
   *
   * @param string $name
   *   The discount name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the discount order types.
   *
   * @return \Drupal\commerce_order\Entity\OrderTypeInterface[]
   *   The discount order types.
   */
  public function getOrderTypes();

  /**
   * Sets the discount order types.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface[] $order_types
   *   The discount order types.
   *
   * @return $this
   */
  public function setOrderTypes(array $order_types);

  /**
   * Gets the discount order type IDs.
   *
   * @return int[]
   *   The discount order type IDs.
   */
  public function getOrderTypeIds();

  /**
   * Sets the discount order type IDs.
   *
   * @param int[] $order_type_ids
   *   The discount order type IDs.
   *
   * @return $this
   */
  public function setOrderTypeIds(array $order_type_ids);

  /**
   * Gets the discount stores.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The discount stores.
   */
  public function getStores();

  /**
   * Sets the discount stores.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface[] $stores
   *   The discount stores.
   *
   * @return $this
   */
  public function setStores(array $stores);

  /**
   * Gets the discount store IDs.
   *
   * @return int[]
   *   The discount store IDs.
   */
  public function getStoreIds();

  /**
   * Sets the discount store IDs.
   *
   * @param int[] $store_ids
   *   The discount store IDs.
   *
   * @return $this
   */
  public function setStoreIds(array $store_ids);

  /**
   * Gets the discount current usage.
   *
   * Represents the number of times the discount was used.
   *
   * @return int
   *   The discount current usage.
   */
  public function getCurrentUsage();

  /**
   * Sets the discount current usage.
   *
   * @param int $current_usage
   *   The discount current usage.
   *
   * @return $this
   */
  public function setCurrentUsage($current_usage);

  /**
   * Gets the discount usage limit.
   *
   * Represents the maximum number of times the discount can be used.
   * 0 for unlimited.
   *
   * @return int
   *   The discount usage limit.
   */
  public function getUsageLimit();

  /**
   * Sets the discount usage limit.
   *
   * @param int $usage_limit
   *   The discount usage limit.
   *
   * @return $this
   */
  public function setUsageLimit($usage_limit);

  /**
   * Gets the discount start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The discount start date.
   */
  public function getStartDate();

  /**
   * Sets the discount start date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The discount start date.
   *
   * @return $this
   */
  public function setStartDate(DrupalDateTime $start_date);

  /**
   * Gets the discount end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The discount end date.
   */
  public function getEndDate();

  /**
   * Sets the discount end date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The discount end date.
   *
   * @return $this
   */
  public function setEndDate(DrupalDateTime $end_date);

  /**
   * Get whether the discount is enabled.
   *
   * @return bool
   *   TRUE if the discount is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets whether the discount is enabled.
   *
   * @param bool $enabled
   *   Whether the discount is enabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

}
