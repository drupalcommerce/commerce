<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Entity\PaymentInfoInterface.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Commerce Payment Information entity.
 */
interface PaymentInfoInterface extends ContentEntityInterface {

  /**
   * Sets the method_id of the payment method that stored the card.
   *
   * @param string $methodId
   *   The method_id of the payment method that stored the card.
   *
   * @return $this
   */
  public function setPaymentMethod($methodId);

  /**
   * Gets the method_id of the payment method that stored the card.
   *
   * @return string
   *   The method_id of the payment method that stored the card.
   */
  public function getPaymentMethod();

  /**
   * Sets the instance identifier for a payment.
   *
   * @param string $instanceId
   *   The instance identifier of the payment.
   *
   * @return $this
   */
  public function setInstanceId($instanceId);

  /**
   * Gets the instance identifier for a payment.
   *
   * @return string
   *   The instance id.
   */
  public function getInstanceId();

  /**
   * Sets the remote identifier for a payment.
   *
   * @param string $remoteId
   *   The remote transaction identifier.
   *
   * @return $this
   */
  public function setRemoteId($remoteId);

  /**
   * Gets the remote identifier for a payment.
   *
   * @return string
   *   The remote id.
   */
  public function getRemoteId();

  /**
   * Sets the default card for this payment method instance.
   *
   * @param integer $default
   *   The default card for this payment method instance.
   *
   * @return $this
   */
  public function setDefault($default);

  /**
   * Gets the default card for this payment method instance.
   *
   * @return integer
   *   The default card for this payment method instance.
   */
  public function getDefault();

  /**
   * Sets the status of this transaction.
   *
   * @param string $status
   *   The status of this transaction (pending, success, or failure).
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Gets the status of this transaction.
   *
   * @return string
   *   The currency code.
   */
  public function getStatus();

  /**
   * Gets the Unix timestamp when this transaction was created.
   *
   * @return int
   *   The Unix timestamp when this transaction was created.
   */
  public function getCreated();

  /**
   * Sets the Unix timestamp when this transaction was last changed.
   *
   * @param array $changed
   *   An Unix timestamp.
   *
   * @return $this
   */
  public function setChanged($changed);

  /**
   * Gets the Unix timestamp when this transaction was last changed.
   *
   * @return int
   *   The Unix timestamp when this transaction was last changed.
   */
  public function getChanged();

  /**
   * Sets additional data for this transaction.
   *
   * @param array $data
   *   The data array.
   *
   * @return $this
   */
  public function setData($data);

  /**
   * Gets additional data for this transaction.
   *
   * @return array
   *   An array of additional data.
   */
  public function getData();

}
