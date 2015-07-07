<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreInterface.
 */

namespace Drupal\commerce_store;

use Drupal\address\AddressInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for stores.
 */
interface StoreInterface extends EntityInterface, EntityOwnerInterface {

  /**
   * Gets the name of the store.
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
   * Gets the e-mail address of the store.
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
   * Gets the default currency for the store.
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
  public function setDefaultCurrency($currencyCode);

  /**
   * Gets the store address.
   *
   * @return \Drupal\address\AddressInterface
   *   The store address.
   */
  public function getAddress();

  /**
   * Sets the store address.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The store address.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setAddress(AddressInterface $address);

  /**
   * Gets the store countries.
   *
   * If empty, it's assumed that the store sells to all countries.
   *
   * @return array
   *   A list of country codes.
   */
  public function getCountries();

  /**
   * Sets the store countries.
   *
   * @param array $countries
   *   A list of country codes.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setCountries(array $countries);

}
