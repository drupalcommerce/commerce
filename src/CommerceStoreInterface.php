<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceStoreInterface.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Commerce Store entity.
 */
interface CommerceStoreInterface extends EntityInterface {
  /**
   * Return the name of the store.
   *
   * @return string
   *   The content of the field.
   */
  public function getName();

  /**
   * Sets the name of the store.
   *
   * @param string $name
   *   The new name of the store.
   *
   * @return \Drupal\commerce\CommerceStoreInterface
   *   The class instance that this method is called on.
   */
  public function setName($name);

  /**
   * Return the e-mail address of the store.
   *
   * @return string
   *   The content of the field.
   */
  public function getEmail();

  /**
   * Sets the e-mail address of the store.
   *
   * @param string $mail
   *   The new e-mail address of the store.
   *
   * @return \Drupal\commerce\CommerceStoreInterface
   *   The class instance that this method is called on.
   */
  public function setEmail($mail);

  /**
   * Return the default currency for the store.
   *
   * @return string
   *   The content of the field.
   */
  public function getDefaultCurrency();

  /**
   * Sets the default currency for the store.
   *
   * @param string $currency_code
   *   The new default currency code of the store.
   *
   * @return \Drupal\commerce\CommerceStoreInterface
   *   The class instance that this method is called on.
   */
  public function setDefaultCurrency($currency_code);

  /**
   * Defines the base fields of the entity type.
   *
   * @param \Drupal\core\Entity\EntityTypeInterface $entity_type
   *   Name of the entity type
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of entity field definitions, keyed by field name.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type);
}
