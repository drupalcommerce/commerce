<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CommerceGuys\Intl\UnknownLocaleException;
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
  }

  /**
   * {@inheritdoc}
   */
  public function importNumberFormat(LanguageInterface $language) {
    if ($this->numberFormatStorage->load($language->getId())) {
      return FALSE;
    }

    if ($number_format = $this->getNumberFormat($language)) {
      $values = array(
        'locale' => $number_format->getLocale(),
        'name' => $language->getName(),
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
      $entity = $this->numberFormatStorage->create($values);
      return $entity;
    }
    return FALSE;
  }

  /**
   * Get a single number format.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   *
   * @return bool|\CommerceGuys\Intl\NumberFormat\NumberFormat
   *   Returns \CommerceGuys\Intl\NumberFormat\NumberFormat or false
   *   when a exception has occurred.
   */
  protected function getNumberFormat(LanguageInterface $language) {
    try {
      $number_format = $this->numberFormatRepository->get($language->getId());
    }
    catch (UnknownLocaleException $e) {
      return FALSE;
    }
    return $number_format;
  }
}
