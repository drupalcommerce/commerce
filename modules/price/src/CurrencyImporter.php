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
   * Creates a new CurrencyImporter object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entityManager, LanguageManagerInterface $languageManager) {
    $this->currencyStorage = $entityManager->getStorage('commerce_currency');
    $this->languageManager = $languageManager;
    $this->currencyRepository = new CurrencyRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportableCurrencies($fallback = CurrencyImporterInterface::FALLBACK_LANGUAGE) {
    $language = $this->languageManager->getCurrentLanguage();
    $importableCurrencies = $this->currencyRepository->getAll($language->getId(), $fallback);
    $importedCurrencies = $this->currencyStorage->loadMultiple();

    // Remove any already imported currencies.
    foreach ($importedCurrencies as $currency) {
      if (isset($importableCurrencies[$currency->id()])) {
        unset($importableCurrencies[$currency->id()]);
      }
    }

    return $importableCurrencies;
  }

  /**
   * {@inheritdoc}
   */
  public function importCurrency($currencyCode) {
    if ($this->currencyStorage->load($currencyCode)) {
      return FALSE;
    }
    $language = $this->languageManager->getDefaultLanguage();
    $currency = $this->getCurrency($currencyCode, $language, CurrencyImporterInterface::FALLBACK_LANGUAGE);

    if ($currency) {
      $values = [
        'currencyCode' => $currency->getCurrencyCode(),
        'name' => $currency->getName(),
        'numericCode' => $currency->getNumericCode(),
        'symbol' => $currency->getSymbol(),
        'fractionDigits' => $currency->getFractionDigits(),
      ];
      $entity = $this->currencyStorage->create($values);

      // Import translations for the new currency.
      $this->importCurrencyTranslations([$entity], $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE));

      return $entity;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importCurrencyTranslations($currencies = [], $languages = []) {
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
        $configName = $currency->getConfigDependencyName();

        $translatedCurrency = $this->getCurrency($currency->getCurrencyCode(), $language);
        $translationExists = $this->languageManager->getLanguageConfigOverrideStorage($language->getId())->exists($configName);
        if (!$translationExists && $translatedCurrency) {
          $configTranslation = $this->languageManager->getLanguageConfigOverride($language->getId(), $configName);
          $configTranslation->set('name', $translatedCurrency->getName());
          $configTranslation->set('symbol', $translatedCurrency->getSymbol());
          $configTranslation->save();
        }
      }
    }
  }

  /**
   * Get a single currency.
   *
   * @param string $currencyCode
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
  protected function getCurrency($currencyCode, LanguageInterface $language, $fallback = NULL) {
    try {
      $currency = $this->currencyRepository->get($currencyCode, $language->getId(), $fallback);
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
