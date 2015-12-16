<?php

/**
 * @file
 * Contains \Drupal\commerce\Resolver\DefaultLocaleResolver.
 */

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
    $language = $this->languageManager->getConfigOverrideLanguage()->getId();
    $language_parts = explode('-', $language);
    if (count($language_parts) > 1 && strlen(end($language_parts)) == 2) {
      // The current language already has a country component (e.g. 'pt-br'),
      // it qualifies as a full locale.
      $locale = $language;
    }
    elseif ($country = $this->countryContext->getCountry()) {
      // Assemble the locale using the resolved country. This can result
      // in non-existent combinations such as 'en-RS', it's up to the locale
      // consumers (e.g. the number format repository) to perform fallback.
      $locale = $language . '-' . $country;
    }
    else {
      // Worst case scenario, the country is unknown.
      $locale = $language;
    }

    return new Locale($locale);
  }

}
