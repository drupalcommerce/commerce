<?php

namespace Drupal\commerce_price;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Exception\UnknownCurrencyException;
use CommerceGuys\Intl\Exception\UnknownLocaleException;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

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
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_currency');
    $this->languageManager = $language_manager;
    $this->externalRepository = new CurrencyRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportable() {
    $imported_currencies = $this->storage->loadMultiple();
    // The getCurrentLanguage() fallback is a workaround for core bug #2684873.
    $language = $this->languageManager->getConfigOverrideLanguage() ?: $this->languageManager->getCurrentLanguage();
    $langcode = $language->getId();
    $all_currencies = $this->externalRepository->getAll($langcode, 'en');
    $importable_currencies = array_diff_key($all_currencies, $imported_currencies);
    $importable_currencies = array_map(function ($currency) {
      return $currency->getName();
    }, $importable_currencies);

    return $importable_currencies;
  }

  /**
   * {@inheritdoc}
   */
  public function import($currency_code) {
    if ($existing_entity = $this->storage->load($currency_code)) {
      // Pretend the currency was just imported.
      return $existing_entity;
    }

    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $currency = $this->externalRepository->get($currency_code, $default_langcode, 'en');
    $values = [
      'langcode' => $default_langcode,
      'currencyCode' => $currency->getCurrencyCode(),
      'name' => $currency->getName(),
      'numericCode' => $currency->getNumericCode(),
      'symbol' => $currency->getSymbol(),
      'fractionDigits' => $currency->getFractionDigits(),
    ];
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $entity */
    $entity = $this->storage->create($values);
    $entity->trustData()->save();
    if ($this->languageManager->isMultilingual()) {
      // Import translations for any additional languages the site has.
      $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
      $languages = array_diff_key($languages, [$default_langcode => $default_langcode]);
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
  public function importByCountry($country_code) {
    $country_repository = new CountryRepository();
    $country = $country_repository->get($country_code);
    $currency_code = $country->getCurrencyCode();
    $entity = NULL;
    if ($currency_code) {
      $entity = $this->import($currency_code);
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
    $currency_code = $currency->getCurrencyCode();
    $config_name = $currency->getConfigDependencyName();
    foreach ($langcodes as $langcode) {
      try {
        $translated_currency = $this->externalRepository->get($currency_code, $langcode);
      }
      catch (UnknownCurrencyException $e) {
        // The currency is custom and doesn't exist in the library.
        return;
      }
      catch (UnknownLocaleException $e) {
        // No translation found.
        continue;
      }

      /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
      $config_translation = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
      if ($config_translation->isNew()) {
        $config_translation->set('name', $translated_currency->getName());
        $config_translation->set('symbol', $translated_currency->getSymbol());
        $config_translation->save();
      }
    }
  }

}
