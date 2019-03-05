<?php

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for objects that contain adjustments.
 *
 * Adjustments store promotions, taxes, fees, shipping costs.
 * They can be calculated on the order level (based on the order subtotal),
 * or on the order item level (based on the order item total).
 *
 * if $order_item->usesLegacyAdjustments() is true, the order item adjustments
 * were calculated based on the order item unit price, which was the default
 * logic prior to Commerce 2.8, changed in #2980713.
 *
 * Adjustments are always displayed in the order total summary, below
 * the subtotal. They are not shown as a part of the order item prices.
 * To get the order item total price with adjustments included, use
 * $order_item->getAdjustedTotalPrice().
 *
 * @see \Drupal\commerce_order\Entity\OrderInterfaceEntity
 * @see \Drupal\commerce_order\Entity\OrderItemInterfaceEntity
 */
interface EntityAdjustableInterface extends EntityInterface {

  /**
   * Gets the adjustments.
   *
   * @param string[] $adjustment_types
   *   The adjustment types to include.
   *   Examples: fee, promotion, tax. Defaults to all adjustment types.
   *
   * @return \Drupal\commerce_order\Adjustment[]
   *   The adjustments.
   */
  public function getAdjustments(array $adjustment_types = []);

  /**
   * Sets the adjustments.
   *
   * @param \Drupal\commerce_order\Adjustment[] $adjustments
   *   The adjustments.
   *
   * @return $this
   */
  public function setAdjustments(array $adjustments);

  /**
   * Adds an adjustment.
   *
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The adjustment.
   *
   * @return $this
   */
  public function addAdjustment(Adjustment $adjustment);

  /**
   * Removes an adjustment.
   *
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The adjustment to remove.
   *
   * @return $this
   */
  public function removeAdjustment(Adjustment $adjustment);

}
