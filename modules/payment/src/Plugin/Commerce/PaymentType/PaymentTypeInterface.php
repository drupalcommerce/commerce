<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentType;

use Drupal\entity\BundlePlugin\BundlePluginInterface;

/**
 * Defines the interface for payment types.
 */
interface PaymentTypeInterface extends BundlePluginInterface {

  /**
   * Gets the payment type label.
   *
   * @return string
   *   The payment type label.
   */
  public function getLabel();

  /**
   * Gets the payment workflow ID.
   *
   * @return string
   *   The payment workflow ID.
   */
  public function getWorkflowId();

}
