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
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\commerce\CountryContextInterface $countryContext
   *   The country context.
   */
  public function __construct(LanguageManagerInterface $languageManager, CountryContextInterface $countryContext) {
    $this->languageManager = $languageManager;
    $this->countryContext = $countryContext;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $language = $this->languageManager->getConfigOverrideLanguage()->getId();
    $languageParts = explode('-', $language);
    if (count($languageParts) > 1 && strlen(end($languageParts)) == 2) {
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
