<?php

/**
 * @file
 * Contains \Drupal\commerce\PaymentTransactionInterface.
 */

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Commerce Payment Transaction entity.
 */
interface PaymentTransactionInterface extends ContentEntityInterface {

  /**
   * Sets the instance identifier for a transaction.
   *
   * @param string $instanceId
   *   The instance identifier of the transaction.
   *
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setInstanceId($instanceId);

  /**
   * Returns the instance identifier for a transaction.
   *
   * @return string
   *   The instance id.
   */
  public function getInstanceId();

  /**
   * Sets the remote identifier for a transaction.
   *
   * @param string $remoteId
   *   The remote transaction identifier.
   *
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setRemoteId($remoteId);

  /**
   * Returns the remote identifier for a transaction.
   *
   * @return string
   *   The remote id.
   */
  public function getRemoteId();

  /**
   * Sets the human-readable message associated to this transaction.
   *
   * @param string|array $message
   *   A human-readable message that is later serialized into the message
   *   column.
   *
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setMessage($message);

  /**
   * Returns human-readable message associated to this transaction.
   *
   * @return string|array
   *   A human-readable message.
   */
  public function getMessage();

  /**
   * Sets the status of this transaction.
   *
   * @param string $status
   *   The status of this transaction (pending, success, or failure).
   *
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setStatus($status);

  /**
   * Returns the status of this transaction.
   *
   * @return string
   *   The status of this transaction.
   */
  public function getStatus();

  /**
   * Sets the remote status of this transaction
   *
   * @param string $remoteStatus
   *   The status of the transaction at the payment provider.
   *
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setRemoteStatus($remoteStatus);

  /**
   * Returns the status of this transaction.
   *
   * @return string
   *   The status of the transaction at the payment provider.
   */
  public function getRemoteStatus();

  /**
   * Sets the payment-gateway specific payload associated with this transaction.
   *
   * @param array $payload
   *   The payment-gateway specific payload.
   *
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setPayload($payload);

  /**
   * Returns the payment-gateway specific payload associated with this
   * transaction.
   *
   * @return string
   *   The payment-gateway specific payload
   */
  public function getPayload();

  /**
   * Returns the Unix timestamp when this transaction was created.
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
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setChanged($changed);

  /**
   * Returns the Unix timestamp when this transaction was last changed.
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
   * @return \Drupal\commerce_payment\PaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setData($data);

  /**
   * Returns additional data for this transaction.
   *
   * @return array
   *   An array of additional data.
   */
  public function getData();

}
