<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Currency\DefaultCurrencyManager;
use CommerceGuys\Intl\Currency\UnknownCurrencyException;
use Drupal\commerce_price\Entity\CommerceCurrency;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;

class CurrencyImporter implements CurrencyImporterInterface {

  /**
   * @var \CommerceGuys\Intl\Currency\CurrencyManagerInterface
   */
  protected $currencyManager;

  /**
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CurrencyImporter.
   *
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(ConfigurableLanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
    $this->currencyManager = new DefaultCurrencyManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportableCurrencies() {
    $language = $this->languageManager->getCurrentLanguage();
    $importable_currencies = $this->currencyManager->getAll($language->getId());
    $imported_currencies = CommerceCurrency::loadMultiple();

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
    if (CommerceCurrency::load($currency_code)) {
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
    $entity = CommerceCurrency::create($values);

    // Import translations for the new currency.
    $this->importCurrencyTranslations(array($entity), $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE));

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function importCurrencyTranslations($currencies = array(), $languages = array()) {
    // Skip importing translations if the site it not multilingual.
    if (!$this->languageManager->isMultilingual()) {
      return FALSE;
    }

    foreach ($currencies as $currency) {
      foreach ($languages as $language) {
        try {
          $translated_entity = $this->currencyManager->get($currency->getCurrencyCode(), $language->getId());
        }
        catch (UnknownCurrencyException $e) {
          // Since currencies manually entered could not be available in the library we ignore them.
          continue;
        }

        $config_name = $currency->getConfigDependencyName();
        if (!$this->languageManager->getLanguageConfigOverrideStorage($language->getId())->exists($config_name)) {
          $config_translation = $this->languageManager->getLanguageConfigOverride($language->getId(), $config_name);
          $config_translation->set('name', $translated_entity->getName());
          $config_translation->set('symbol', $translated_entity->getSymbol());
          $config_translation->save();
        }
      }
    }
  }
}
