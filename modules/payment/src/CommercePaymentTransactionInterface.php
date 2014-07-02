<?php

/**
 * @file
 * Contains \Drupal\commerce\CommercePaymentTransactionInterface.
 */

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Commerce Transaction entity.
 */
interface CommercePaymentTransactionInterface extends ContentEntityInterface {
  /**
   * Sets the instance identifier for a transaction.
   *
   * @param string $instance_id
   *   The instance identifier of the transaction.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setInstanceId($instance_id);


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
   * @param string $remote_id
   *   The remote transaction identifier.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setRemoteId($remote_id);

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
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
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
   * Sets the amount of this transaction.
   *
   * @param int $amount
   *   An integer amount.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setAmount($amount);

  /**
   * Returns the amount of this transaction.
   *
   * @return int
   *   The amount of this transaction.
   */
  public function getAmount();

  /**
   * Sets the currency code for a transaction.
   *
   * @param string $currency_code
   *   The new default currency code of the transaction.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setCurrencyCode($currency_code);

  /**
   * Returns the currency code for a transaction.
   *
   * @return string
   *   The currency code.
   */
  public function getCurrencyCode();

  /**
   * Sets the status of this transaction.
   *
   * @param string $status
   *   The status of this transaction (pending, success, or failure).
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setStatus($status);

  /**
   * Returns the status of this transaction.
   *
   * @return string
   *   The currency code.
   */
  public function getStatus();

  /**
   * Sets the remote status of this transaction
   *
   * @param string $remote_status
   *   The status of the transaction at the payment provider.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setRemoteStatus($remote_status);

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
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
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
  public function created();

  /**
   * Sets the Unix timestamp when this transaction was last changed.
   *
   * @param array $changed
   *   An Unix timestamp.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
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

  /**
   * Defines the base fields of the entity type.
   *
   * @param EntityTypeInterface $entity_type
   *   Name of the entity type
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   An array of entity field definitions, keyed by field name.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type);
}
