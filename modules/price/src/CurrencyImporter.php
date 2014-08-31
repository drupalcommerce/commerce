<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Currency\DefaultCurrencyManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

class CurrencyImporter implements CurrencyImporterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * @var \CommerceGuys\Intl\Currency\CurrencyManagerInterface
   */
  protected $currencyManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CurrencyImporter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entityManager, LanguageManagerInterface $languageManager) {
    $this->currencyStorage = $entityManager->getStorage('commerce_currency');
    $this->languageManager = $languageManager;
    $this->currencyManager = new DefaultCurrencyManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportableCurrencies() {
    $language = $this->languageManager->getCurrentLanguage();
    $importable_currencies = $this->currencyManager->getAll($language->getId());
    $imported_currencies = $this->currencyStorage->loadMultiple();

    // Remove any already imported currencies.
    foreach($imported_currencies as $currency) {
      if (isset($importable_currencies[$currency->id()])) {
        unset($importable_currencies[$currency->id()]);
      }
    }

    return $importable_currencies;
  }

  /**
   * {@inheritdoc}
   */
  public function importCurrency($currency_code) {
    if ($this->currencyStorage->load($currency_code)) {
      return FALSE;
    }
    $language = $this->languageManager->getCurrentLanguage();
    $currency = $this->currencyManager->get($currency_code, $language->getId());

    $values = array(
      'currencyCode' => $currency->getCurrencyCode(),
      'name' => $currency->getName(),
      'numericCode' => $currency->getNumericCode(),
      'symbol' => $currency->getSymbol(),
      'fractionDigits' => $currency->getFractionDigits(),
    );
    $entity = $this->currencyStorage->create($values);

    return $entity;
  }
}
