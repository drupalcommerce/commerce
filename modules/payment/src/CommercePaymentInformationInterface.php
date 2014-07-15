<?php

/**
 * @file
 * Contains \Drupal\commerce\CommercePaymentInformationInterface.
 */

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Commerce Payment Information entity.
 */
interface CommercePaymentInformationInterface extends ContentEntityInterface {
  /**
   * Sets the method_id of the payment method that stored the card.
   *
   * @param string $method_id
   *   The method_id of the payment method that stored the card.
   * 
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
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
   * Sets the card type for a payment.
   *
   * @param string $card_type
   *   The card type.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
   *   The class instance that this method is called on.
   */
  public function setCardType($card_type);

  /**
   * Returns the card type for a payment.
   *
   * @return string
   *   The card type.
   */
  public function getCardType();

  /**
   * Sets the card name for a payment.
   *
   * @param string $card_name
   *   The card name.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
   *   The class instance that this method is called on.
   */
  public function setCardName($card_name);

  /**
   * Returns the card name for a payment.
   *
   * @return string
   *   The card name.
   */
  public function getCardName();

  /**
   * Sets the truncated card number (last 4 digits).
   *
   * @param string $card_number
   *   The card number.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
   *   The class instance that this method is called on.
   */
  public function setCardNumber($card_number);

  /**
   * Returns the truncated card number (last 4 digits).
   *
   * @return string
   *   The last 4 digits of a card associated with a payment.
   */
  public function getCardNumber();

  /**
   * Sets the card's expiration month.
   *
   * @param integer $card_exp_month
   *   The card's expiration month.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
   *   The class instance that this method is called on.
   */
  public function setCardExpMonth($card_exp_month);

  /**
   * Returns the card's expiration month.
   *
   * @return integer
   *   The card's expiration month.
   */
  public function getCardExpMonth();

  /**
   * Sets the card's expiration year.
   *
   * @param integer $card_exp_year
   *   The card's expiration year.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
   *   The class instance that this method is called on.
   */
  public function setCardExpYear($card_exp_year);

  /**
   * Returns the card's expiration year.
   *
   * @return integer
   *   The card's expiration year.
   */
  public function getCardExpYear();

  /**
   * Sets the default card for this payment method instance.
   *
   * @param integer $instance_default
   *   Whether this is the default card for this payment method instance.
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
   *   The class instance that this method is called on.
   */
  public function setInstanceDefault($instance_default);

  /**
   * Returns the default card for this payment method instance.
   *
   * @return integer
   *   Whether this is the default card for this payment method instance.
   */
  public function getInstanceDefault();

  /**
   * Sets the status of this transaction.
   *
   * @param string $status
   *   The status of this transaction (pending, success, or failure).
   *
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
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
   * @return \Drupal\commerce_payment\CommercePaymentInformationInterface
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
