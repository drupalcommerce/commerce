<?php

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for order types.
 */
interface OrderTypeInterface extends ConfigEntityInterface {

  // Refresh modes.
  const REFRESH_ALWAYS = 'always';
  const REFRESH_CUSTOMER = 'customer';

  /**
   * Gets the order type's workflow ID.
   *
   * Used by the $order->state field.
   *
   * @return string
   *   The order type workflow ID.
   */
  public function getWorkflowId();

  /**
   * Sets the workflow ID of the order type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId($workflow_id);

  /**
   * Gets the order type's refresh mode.
   *
   * Used by the order refresh process.
   *
   * @return string
   *   The refresh mode.
   */
  public function getRefreshMode();

  /**
   * Sets the refresh mode for the order type.
   *
   * @param string $refresh_mode
   *   The refresh mode.
   *
   * @return $this
   */
  public function setRefreshMode($refresh_mode);

  /**
   * Gets the order type's refresh frequency.
   *
   * @return int
   *   The frequency, in seconds.
   */
  public function getRefreshFrequency();

  /**
   * Sets the refresh frequency for the order type.
   *
   * @param int $refresh_frequency
   *   The frequency, in seconds.
   *
   * @return $this
   */
  public function setRefreshFrequency($refresh_frequency);

  /**
   * Whether to send the customer an email when the order is placed.
   *
   * @return bool
   *   TRUE when an email should be sent, otherwise FALSE.
   */
  public function shouldSendReceipt();

  /**
   * Set whether to send a customer email when an order is placed.
   *
   * @param bool $send
   *   TRUE when an email should be sent, or FALSE.
   *
   * @return $this
   */
  public function setSendReceipt($send);

  /**
   * Whether to send the admin an email when the order is placed.
   *
   * @return bool
   *   TRUE when an email should be sent, otherwise FALSE.
   */
  public function shouldAddReceiptBcc();

  /**
   * Set whether to send an admin email when an order is placed.
   *
   * @param bool $send
   *   TRUE when an email should be sent, or FALSE.
   *
   * @return $this
   */
  public function setReceiptBcc($send);

}
