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
   * Gets the adjustment type weight.
   *
   * @return int
   *   The adjustment type weight.
   */
  public function getWeight();

  /**
   * Gets whether the adjustment type can be created from the UI.
   *
   * @return bool
   *   TRUE if the adjustment type can be created from the UI, FALSE otherwise.
   */
  public function hasUi();

}
