<?php

namespace Drupal\commerce_price\Repository;

use CommerceGuys\Intl\Currency\Currency;
use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\Exception\UnknownCurrencyException;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines the currency repository.
 *
 * Provides currencies to the CurrencyFormatter in the expected format,
 * loaded from Drupal's currency storage (commerce_currency entities).
 *
 * Note: This repository doesn't support loading currencies in a non-default
 * locale, since it would be imprecise to map $locale to Drupal's languages.
 */
class CurrencyRepository implements CurrencyRepositoryInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Creates an CurrencyRepository instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public function get($currency_code, $locale = NULL) {
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $this->currencyStorage->load($currency_code);
    if (!$currency) {
      throw new UnknownCurrencyException($currency_code);
    }

    return $this->createValueObjectFromEntity($currency);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($locale = NULL) {
    $all = [];
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $this->currencyStorage->loadMultiple();
    foreach ($currencies as $currency_code => $currency) {
      $all[$currency_code] = $this->createValueObjectFromEntity($currency);
    }

    return $all;

  }

  /**
   * {@inheritdoc}
   */
  public function getList($locale = NULL) {
    $list = [];
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $entities */
    $currencies = $this->currencyStorage->loadMultiple();
    foreach ($currencies as $currency_code => $currency) {
      $list[$currency_code] = $currency->getName();
    }

    return $list;
  }

  /**
   * Creates a currency value object from the given entity.
   *
   * @param \Drupal\commerce_price\Entity\CurrencyInterface $currency
   *   The currency entity.
   *
   * @return \CommerceGuys\Intl\Currency\Currency
   *   The currency value object.
   */
  protected function createValueObjectFromEntity(CurrencyInterface $currency) {
    return new Currency([
      'currency_code' => $currency->getCurrencyCode(),
      'name' => $currency->getName(),
      'numeric_code' => $currency->getNumericCode(),
      'symbol' => $currency->getSymbol(),
      'fraction_digits' => $currency->getFractionDigits(),
      'locale' => $currency->language()->getId(),
    ]);
  }

}
