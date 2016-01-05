<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\Currency.
 */

namespace Drupal\commerce_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the currency entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_currency",
 *   label = @Translation("Currency"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_price\Form\CurrencyForm",
 *       "edit" = "Drupal\commerce_price\Form\CurrencyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_price\CurrencyListBuilder",
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_currency",
 *   entity_keys = {
 *     "id" = "currencyCode",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "currencyCode",
 *     "name",
 *     "numericCode",
 *     "symbol",
 *     "fractionDigits"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/currency/{commerce_currency}",
 *     "delete-form" = "/admin/commerce/config/currency/{commerce_currency}/delete",
 *     "collection" = "/admin/commerce/config/currency"
 *   }
 * )
 */
class Currency extends ConfigEntityBase implements CurrencyInterface {

  /**
   * The alphanumeric currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * The currency name.
   *
   * @var string
   */
  protected $name;

  /**
   * The numeric currency code.
   *
   * @var string
   */
  protected $numericCode;

  /**
   * The currency symbol.
   *
   * @var string
   */
  protected $symbol;

  /**
   * The number of fraction digits.
   *
   * @var int
   */
  protected $fractionDigits;

  /**
   * Overrides \Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->currencyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($currency_code) {
    $this->currencyCode = $currency_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumericCode() {
    return $this->numericCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setNumericCode($numeric_code) {
    $this->numericCode = $numeric_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSymbol() {
    return $this->symbol;
  }

  /**
   * {@inheritdoc}
   */
  public function setSymbol($symbol) {
    $this->symbol = $symbol;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFractionDigits() {
    return $this->fractionDigits;
  }

  /**
   * {@inheritdoc}
   */
  public function setFractionDigits($fraction_digits) {
    $this->fractionDigits = $fraction_digits;
    return $this;
  }

}
