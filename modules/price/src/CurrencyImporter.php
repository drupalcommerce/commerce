<?php

/**
 * @file
 * Contains \Drupal\commerce_price\CurrencyImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Country\CountryRepository;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Exception\UnknownLocaleException;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;

/**
 * Default implementation of the currency importer.
 */
class CurrencyImporter implements CurrencyImporterInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The library's currency repository.
   *
   * @var \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface
   */
  protected $externalRepository;

  /**
   * Creates a new CurrencyImporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager) {
    $this->storage = $entityTypeManager->getStorage('commerce_currency');
    $this->languageManager = $languageManager;
    $this->externalRepository = new CurrencyRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportable() {
    $importedCurrencies = $this->storage->loadMultiple();
    $langcode = $this->languageManager->getConfigOverrideLanguage()->getId();
    $allCurrencies = $this->externalRepository->getAll($langcode, 'en');
    $importableCurrencies = array_diff_key($allCurrencies, $importedCurrencies);
    $importableCurrencies = array_map(function ($currency) {
      return $currency->getName();
    }, $importableCurrencies);

    return $importableCurrencies;
  }

  /**
   * {@inheritdoc}
   */
  public function import($currencyCode) {
    if ($existingEntity = $this->storage->load($currencyCode)) {
      // Pretend the currency was just imported.
      return $existingEntity;
    }

    $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();
    $currency = $this->externalRepository->get($currencyCode, $defaultLangcode, 'en');
    $values = [
      'langcode' => $defaultLangcode,
      'currencyCode' => $currency->getCurrencyCode(),
      'name' => $currency->getName(),
      'numericCode' => $currency->getNumericCode(),
      'symbol' => $currency->getSymbol(),
      'fractionDigits' => $currency->getFractionDigits(),
    ];
    $entity = $this->storage->create($values);
    $entity->trustData()->save();
    if ($this->languageManager->isMultilingual()) {
      // Import translations for any additional languages the site has.
      $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
      $languages = array_diff_key($languages, [$defaultLangcode => $defaultLangcode]);
      $langcodes = array_map(function ($language) {
        return $language->getId();
      }, $languages);
      $this->importEntityTranslations($entity, $langcodes);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function importByCountry($countryCode) {
    $countryRepository = new CountryRepository();
    $country = $countryRepository->get($countryCode);
    $currencyCode = $country->getCurrencyCode();
    $entity = NULL;
    if ($currencyCode) {
      $entity = $this->import($currencyCode);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function importTranslations(array $langcodes) {
    foreach ($this->storage->loadMultiple() as $currency) {
      $this->importEntityTranslations($currency, $langcodes);
    }
  }

  /**
   * Imports translations for the given currency entity.
   *
   * @param \Drupal\commerce_price\Entity\CurrencyInterface $currency
   *   The currency entity.
   * @param array $langcodes
   *   The langcodes.
   */
  protected function importEntityTranslations(CurrencyInterface $currency, array $langcodes) {
    $currencyCode = $currency->getCurrencyCode();
    $configName = $currency->getConfigDependencyName();
    foreach ($langcodes as $langcode) {
      try {
        $translatedCurrency = $this->externalRepository->get($currencyCode, $langcode);
      }
      catch (UnknownLocaleException $e) {
        // No translation found.
        continue;
      }

      $configTranslation = $this->languageManager->getLanguageConfigOverride($langcode, $configName);
      if ($configTranslation->isNew()) {
        $configTranslation->set('name', $translatedCurrency->getName());
        $configTranslation->set('symbol', $translatedCurrency->getSymbol());
        $configTranslation->save();
      }
    }
  }

}
