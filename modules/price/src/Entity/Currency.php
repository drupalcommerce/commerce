<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\Currency.
 */

namespace Drupal\commerce_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Currency configuration entity.
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
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "currencyCode",
 *     "name",
 *     "numericCode",
 *     "symbol",
 *     "fractionDigits",
 *     "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/currency/{commerce_currency}",
 *     "delete-form" = "/admin/commerce/config/currency/{commerce_currency}/delete",
 *     "enable" = "/admin/commerce/config/currency/{commerce_currency}/enable",
 *     "disable" = "/admin/commerce/config/currency/{commerce_currency}/disable",
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
  public function setCurrencyCode($currencyCode) {
    $this->currencyCode = $currencyCode;
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
  public function setNumericCode($numericCode) {
    $this->numericCode = $numericCode;
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
  public function setFractionDigits($fractionDigits) {
    $this->fractionDigits = $fractionDigits;
    return $this;
  }

}
