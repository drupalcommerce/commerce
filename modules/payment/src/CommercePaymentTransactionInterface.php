<?php

/**
 * @file
 * Contains \Drupal\commerce\CommercePaymentTransactionInterface.
 */

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Commerce Transaction entity.
 */
interface CommercePaymentTransactionInterface extends EntityInterface {
  /**
   * Returns the identifier.
   *
   * @return int
   *   The entity identifier.
   */
  public function id();

  /**
   * Returns the revision identifier
   *
   * @return int
   *   The entity revision identifier.
   */
  public function revisionId();

  /**
   * Returns the entity UUID (Universally Unique Identifier).
   *
   * The UUID is guaranteed to be unique and can be used to identify an entity
   * across multiple systems.
   *
   * @return string
   *   The UUID of the entity.
   */
  public function uuid();

  /**
   * Return the label of the transaction.
   *
   * @return string
   *   The content of the field.
   */
  public function getLabel();

  /**
   * Sets the label of the transaction.
   *
   * @param string $label
   *   The new name of the transaction.
   *
   * @return \Drupal\commerce_payment\CommercePaymentTransactionInterface
   *   The class instance that this method is called on.
   */
  public function setLabel($label);

  /**
   * Return the currency code for a transaction.
   *
   * @return string
   *   The content of the field.
   */
  public function getCurrencyCode();

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
