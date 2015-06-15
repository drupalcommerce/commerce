<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreInterface.
 */

namespace Drupal\commerce_store;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for stores.
 */
interface StoreInterface extends EntityInterface, EntityOwnerInterface {

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
   * @return \Drupal\commerce_store\StoreInterface
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
   * @return \Drupal\commerce_store\StoreInterface
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
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setDefaultCurrency($currency_code);

}
