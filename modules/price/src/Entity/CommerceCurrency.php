<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\CommerceOrderType.
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
 *     "list_builder" = "Drupal\commerce_price\Controller\CommerceCurrencyListBuilder",
 *   },
 *   admin_permission = "administer commerce_store entities",
 *   config_prefix = "commerce_currency",
 *   entity_keys = {
 *     "id" = "currencyCode",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "entity.commerce_currency.edit_form",
 *     "delete-form" = "entity.commerce_currency.delete_form"
 *   }
 * )
 */
class CommerceCurrency extends ConfigEntityBase implements CurrencyInterface {

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
    return $this->getCurrencyCode();
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
