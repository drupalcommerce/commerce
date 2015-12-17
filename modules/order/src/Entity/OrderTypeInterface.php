<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\OrderTypeInterface.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for order types.
 */
interface OrderTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the workflow of the order type.
   *
   * Used by the $order->state field.
   *
   * @return string
   *   The order type workflow.
   */
  public function getWorkflow();

  /**
   * Sets the workflow of the order type.
   *
   * @param string $workflow
   *   The workflow.
   *
   * @return $this
   */
  public function setWorkflow($workflow);

}
