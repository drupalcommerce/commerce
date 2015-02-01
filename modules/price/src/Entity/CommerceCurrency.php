<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\CommerceCurrency.
 */

namespace Drupal\commerce_price\Entity;

use CommerceGuys\Intl\Currency\CurrencyInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Currency configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_currency",
 *   label = @Translation("Currency"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_price\Form\CommerceCurrencyForm",
 *       "edit" = "Drupal\commerce_price\Form\CommerceCurrencyForm",
 *       "delete" = "Drupal\commerce_price\Form\CommerceCurrencyDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_price\CommerceCurrencyListBuilder",
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_currency",
 *   entity_keys = {
 *     "id" = "currency_code",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/currency/{commerce_currency}",
 *     "delete-form" = "/admin/commerce/config/currency/{commerce_currency}/delete"
 *   }
 * )
 */
class CommerceCurrency extends ConfigEntityBase implements CurrencyInterface {

  /**
   * The alphanumeric currency code.
   *
   * @var string
   */
  protected $currency_code;

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
    return $this->getCurrencyCode();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->currency_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($currency_code) {
    $this->currency_code = $currency_code;

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
