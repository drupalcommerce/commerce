<?php

namespace Drupal\commerce_order\Plugin\Commerce\AdjustmentType;

/**
 * Defines the interface for adjustment types.
 */
interface AdjustmentTypeInterface {

  /**
   * Gets the adjustment type ID.
   *
   * @return string
   *   The adjustment type ID.
   */
  public function getId();

  /**
   * Gets the adjustment type label.
   *
   * @return string
   *   The adjustment type label.
   */
  public function getLabel();

  /**
   * Gets the weight of the adjustment type.
   *
   * @return int
   *   The weight of the adjustment type.
   */
  public function getWeight();

  /**
   * Gets whether or not the adjustment type can be created from the UI.
   *
   * @return bool
   *   TRUE if the adjustment type can be created from the UI.
   */
  public function hasUi();

}
