<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Exception\UnknownCurrencyException;
use CommerceGuys\Intl\Exception\UnknownLocaleException;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;

class CurrencyImporter implements CurrencyImporterInterface {

  /**
   * The currency manager.
   *
   * @var \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface
   */
  protected $currencyRepository;

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The configurable language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CurrencyImporter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->currencyStorage = $entity_manager->getStorage('commerce_currency');
    $this->languageManager = $language_manager;
    $this->currencyRepository = new CurrencyRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportableCurrencies($fallback = CurrencyImporterInterface::FALLBACK_LANGUAGE) {
    $language = $this->languageManager->getCurrentLanguage();
    $importable_currencies = $this->currencyRepository->getAll($language->getId(), $fallback);
    $imported_currencies = $this->currencyStorage->loadMultiple();

    // Remove any already imported currencies.
    foreach ($imported_currencies as $currency) {
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
    $language = $this->languageManager->getDefaultLanguage();
    $currency = $this->getCurrency($currency_code, $language, CurrencyImporterInterface::FALLBACK_LANGUAGE);

    if ($currency) {
      $values = array(
        'currencyCode' => $currency->getCurrencyCode(),
        'name' => $currency->getName(),
        'numericCode' => $currency->getNumericCode(),
        'symbol' => $currency->getSymbol(),
        'fractionDigits' => $currency->getFractionDigits(),
      );
      $entity = $this->currencyStorage->create($values);

      // Import translations for the new currency.
      $this->importCurrencyTranslations(array($entity), $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE));

      return $entity;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importCurrencyTranslations($currencies = array(), $languages = array()) {
    // Skip importing translations if the site it not multilingual.
    if (!$this->languageManager->isMultilingual() || !$this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      return FALSE;
    }

    foreach ($currencies as $currency) {
      foreach ($languages as $language) {
        // Don't add a translation for the original language.
        if ($currency->language()->getId() === $language->getId()) {
          continue;
        }
        $config_name = $currency->getConfigDependencyName();

        $translated_currency = $this->getCurrency($currency->getCurrencyCode(), $language);
        $translation_exists = $this->languageManager->getLanguageConfigOverrideStorage($language->getId())->exists($config_name);
        if (!$translation_exists && $translated_currency) {
          $config_translation = $this->languageManager->getLanguageConfigOverride($language->getId(), $config_name);
          $config_translation->set('name', $translated_currency->getName());
          $config_translation->set('symbol', $translated_currency->getSymbol());
          $config_translation->save();
        }
      }
    }
  }

  /**
   * Get a single currency.
   *
   * @param string $currency_code
   *   The currency code.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   * @param string $fallback
   *   The fallback language code.
   *
   * @return bool|\CommerceGuys\Intl\Currency\Currency
   *   Returns \CommerceGuys\Intl\Currency\Currency or
   *   false when a exception has occurred.
   */
  protected function getCurrency($currency_code, LanguageInterface $language, $fallback = NULL) {
    try {
      $currency = $this->currencyRepository->get($currency_code, $language->getId(), $fallback);
    }
    catch (UnknownLocaleException $e) {
      return FALSE;
    }
    catch (UnknownCurrencyException $e) {
      return FALSE;
    }

    return $currency;
  }
}
