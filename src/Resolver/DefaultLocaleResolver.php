<?php

namespace Drupal\commerce\Resolver;

use Drupal\commerce\CountryContextInterface;
use Drupal\commerce\Locale;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Returns the locale based on the current language and country.
 */
class DefaultLocaleResolver implements LocaleResolverInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The country context.
   *
   * @var \Drupal\commerce\CountryContextInterface
   */
  protected $countryContext;

  /**
   * Constructs a new DefaultLocaleResolver object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\commerce\CountryContextInterface $country_context
   *   The country context.
   */
  public function __construct(LanguageManagerInterface $language_manager, CountryContextInterface $country_context) {
    $this->languageManager = $language_manager;
    $this->countryContext = $country_context;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    // The getCurrentLanguage() fallback is a workaround for core bug #2684873.
    $language = $this->languageManager->getConfigOverrideLanguage() ?: $this->languageManager->getCurrentLanguage();
    $langcode = $language->getId();
    $langcode_parts = explode('-', $langcode);
    if (count($langcode_parts) > 1 && strlen(end($langcode_parts)) == 2) {
      // The current language already has a country component (e.g. 'pt-br'),
      // it qualifies as a full locale.
      $locale = $langcode;
    }
    elseif ($country = $this->countryContext->getCountry()) {
      // Assemble the locale using the resolved country. This can result
      // in non-existent combinations such as 'en-RS', it's up to the locale
      // consumers (e.g. the number format repository) to perform fallback.
      $locale = $langcode . '-' . $country;
    }
    else {
      // Worst case scenario, the country is unknown.
      $locale = $langcode;
    }

    return new Locale($locale);
  }

}
