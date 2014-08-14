<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\CommercePaymentInfoInterface.
 */

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Commerce Payment Information entity.
 */
interface CommercePaymentInfoInterface extends ContentEntityInterface {

  /**
   * Sets the method_id of the payment method that stored the card.
   *
   * @param string $method_id
   *   The method_id of the payment method that stored the card.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
   *   The class instance that this method is called on.
   */
  public function setPaymentMethod($method_id);

  /**
   * Returns the method_id of the payment method that stored the card.
   *
   * @return string
   *   The method_id of the payment method that stored the card.
   */
  public function getPaymentMethod();

  /**
   * Sets the instance identifier for a payment.
   *
   * @param string $instance_id
   *   The instance identifier of the payment.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
   *   The class instance that this method is called on.
   */
  public function setInstanceId($instance_id);

  /**
   * Returns the instance identifier for a payment.
   *
   * @return string
   *   The instance id.
   */
  public function getInstanceId();

  /**
   * Sets the remote identifier for a payment.
   *
   * @param string $remote_id
   *   The remote transaction identifier.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
   *   The class instance that this method is called on.
   */
  public function setRemoteId($remote_id);

  /**
   * Returns the remote identifier for a payment.
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
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
   *   The class instance that this method is called on.
   */
  public function setDefault($default);

  /**
   * Returns the default card for this payment method instance.
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
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentInfoInterface
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
