<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreInterface.
 */

namespace Drupal\commerce_store;

use Drupal\commerce_price\CurrencyInterface;
use Drupal\address\AddressInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for stores.
 */
interface StoreInterface extends EntityInterface, EntityOwnerInterface {

  /**
   * Gets the store name.
   *
   * @return string
   *   The store name.
   */
  public function getName();

  /**
   * Sets the store name.
   *
   * @param string $name
   *   The store name.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setName($name);

  /**
   * Gets the store e-mail.
   *
   * @return string
   *   The store e-mail
   */
  public function getEmail();

  /**
   * Sets the store e-mail.
   *
   * @param string $mail
   *   The store e-mail.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setEmail($mail);

  /**
   * Gets the default store currency.
   *
   * @return \Drupal\commerce_price\CurrencyInterface
   *   The default store currency.
   */
  public function getDefaultCurrency();

  /**
   * Sets the default store currency.
   *
   * @param \Drupal\commerce_price\CurrencyInterface $currency
   *   The default store currency.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setDefaultCurrency(CurrencyInterface $currency);

  /**
   * Gets the default store currency code.
   *
   * @return string
   *   The default store currency code.
   */
  public function getDefaultCurrencyCode();

  /**
   * Sets the default store currency code.
   *
   * @param string $currencyCode
   *   The default store currency code.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The class instance that this method is called on.
   */
  public function setDefaultCurrencyCode($currencyCode);

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
