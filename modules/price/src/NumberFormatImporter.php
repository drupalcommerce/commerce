<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CommerceGuys\Intl\Language\LanguageRepository;
use CommerceGuys\Intl\Exception\UnknownLocaleException;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;

class NumberFormatImporter {

  /**
   * The number format repository.
   *
   * @var \CommerceGuys\Intl\NumberFormat\NumberFormatRepository
   */
  protected $numberFormatRepository;

  /**
   * The language repository.
   *
   * @var \CommerceGuys\Intl\Language\LanguageRepository
   */
  protected $languageRepository;

  /**
   * The number format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $numberFormatStorage;

  /**
   * Constructs a new NumberFormatImporter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->numberFormatStorage = $entity_manager->getStorage('commerce_number_format');
    $this->numberFormatRepository = new NumberFormatRepository();
    $this->languageRepository = new LanguageRepository();
  }

  /**
   * Imports for all language variants for a given language the number format.
   *
   * @param |Drupal\Core\Language\LanguageInterface $language
   *   The $language
   */
  public function importNumberFormats(LanguageInterface $language) {
    $language_variants = $this->getLanguageVariants($language);

    foreach ($language_variants as $language_variant) {
      if ($number_format = $this->getNumberFormat($language_variant->getLanguageCode())) {

        // Skip if current number format is already imported.
        if ($this->numberFormatStorage->load($number_format->getLocale())) {
          continue;
        }

        $values = array(
          'locale' => $number_format->getLocale(),
          'name' => $language_variant->getName(),
          'numberingSystem' => $number_format->getNumberingSystem(),
          'decimalSeparator' => $number_format->getDecimalSeparator(),
          'groupingSeparator' => $number_format->getGroupingSeparator(),
          'plusSign' => $number_format->getPlusSign(),
          'minusSign' => $number_format->getMinusSign(),
          'percentSign' => $number_format->getPercentSign(),
          'decimalPattern' => $number_format->getDecimalPattern(),
          'percentPattern' => $number_format->getPercentPattern(),
          'currencyPattern' => $number_format->getCurrencyPattern(),
          'accountingCurrencyPattern' => $number_format->getAccountingCurrencyPattern(),
        );

        $this->numberFormatStorage->create($values)->save();
      }
    }
    return FALSE;
  }

  /**
   * Get a single number format.
   *
   * @param $language_code
   *   The language code
   *
   * @return bool|\CommerceGuys\Intl\NumberFormat\NumberFormat
   *   Returns \CommerceGuys\Intl\NumberFormat\NumberFormat or false
   *   when a exception has occurred.
   */
  protected function getNumberFormat($language_code) {
    try {
      $number_format = $this->numberFormatRepository->get($language_code);
    }
    catch (UnknownLocaleException $e) {
      return FALSE;
    }
    return $number_format;
  }

  /**
   * Get all language variants for a language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   * The language.
   *
   * @return bool|array An array of languages implementing
   * CommerceGuys\Intl\Language\LanguageInterface keyed by language code, or
   * false when an exception has ocurred.
   */
  protected function getLanguageVariants($language) {
    try {
      $all_languages = $this->languageRepository->getAll();
      $language_code = current(explode('-', $language->getId()));
      $pattern = '/(^' . $language_code . '-)|(^' . $language_code . ')/';
      $language_variants = array_intersect_key($all_languages, array_flip(preg_grep($pattern, array_keys($all_languages))));
    } catch (UnknownLocaleException $e) {
      return FALSE;
    }
    return $language_variants;
  }
}
