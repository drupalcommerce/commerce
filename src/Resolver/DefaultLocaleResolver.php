<?php

namespace Drupal\commerce\Resolver;

use Drupal\commerce\CurrentCountryInterface;
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
   * The current country.
   *
   * @var \Drupal\commerce\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * Constructs a new DefaultLocaleResolver object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\commerce\CurrentCountryInterface $current_country
   *   The current country.
   */
  public function __construct(LanguageManagerInterface $language_manager, CurrentCountryInterface $current_country) {
    $this->languageManager = $language_manager;
    $this->currentCountry = $current_country;
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
    elseif ($country = $this->currentCountry->getCountry()) {
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
