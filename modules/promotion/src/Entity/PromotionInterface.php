<?php

namespace Drupal\commerce_promotion\Entity;

use Drupal\commerce_store\Entity\EntityStoresInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for promotions.
 */
interface PromotionInterface extends EntityInterface, EntityStoresInterface {

  /**
   * Gets the promotion name.
   *
   * @return string
   *   The promotion name.
   */
  public function getName();

  /**
   * Sets the promotion name.
   *
   * @param string $name
   *   The promotion name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the promotion description.
   *
   * @return string
   *    The promotion description.
   */
  public function getDescription();

  /**
   * Sets the promotion description.
   *
   * @param string $description
   *   The promotion description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the promotion order types.
   *
   * @return \Drupal\commerce_order\Entity\OrderTypeInterface[]
   *   The promotion order types.
   */
  public function getOrderTypes();

  /**
   * Sets the promotion order types.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface[] $order_types
   *   The promotion order types.
   *
   * @return $this
   */
  public function setOrderTypes(array $order_types);

  /**
   * Gets the promotion order type IDs.
   *
   * @return int[]
   *   The promotion order type IDs.
   */
  public function getOrderTypeIds();

  /**
   * Sets the promotion order type IDs.
   *
   * @param int[] $order_type_ids
   *   The promotion order type IDs.
   *
   * @return $this
   */
  public function setOrderTypeIds(array $order_type_ids);

  /**
   * Gets the promotion current usage.
   *
   * Represents the number of times the promotion was used.
   *
   * @return int
   *   The promotion current usage.
   */
  public function getCurrentUsage();

  /**
   * Sets the promotion current usage.
   *
   * @param int $current_usage
   *   The promotion current usage.
   *
   * @return $this
   */
  public function setCurrentUsage($current_usage);

  /**
   * Gets the promotion usage limit.
   *
   * Represents the maximum number of times the promotion can be used.
   * 0 for unlimited.
   *
   * @return int
   *   The promotion usage limit.
   */
  public function getUsageLimit();

  /**
   * Sets the promotion usage limit.
   *
   * @param int $usage_limit
   *   The promotion usage limit.
   *
   * @return $this
   */
  public function setUsageLimit($usage_limit);

  /**
   * Gets the promotion start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The promotion start date.
   */
  public function getStartDate();

  /**
   * Sets the promotion start date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The promotion start date.
   *
   * @return $this
   */
  public function setStartDate(DrupalDateTime $start_date);

  /**
   * Gets the promotion end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The promotion end date.
   */
  public function getEndDate();

  /**
   * Sets the promotion end date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The promotion end date.
   *
   * @return $this
   */
  public function setEndDate(DrupalDateTime $end_date);

  /**
   * Get whether the promotion is enabled.
   *
   * @return bool
   *   TRUE if the promotion is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets whether the promotion is enabled.
   *
   * @param bool $enabled
   *   Whether the promotion is enabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

  /**
   * Checks whether the promotion entity can be applied.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if promotion can be applied, or false if conditions failed.
   */
  public function applies(EntityInterface $entity);

  /**
   * Apply the promotion to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function apply(EntityInterface $entity);

}
