<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatImporter.
 */

namespace Drupal\commerce_price;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
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
   * The number format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $numberFormatStorage;

  /**
   * Constructs a new NumberFormatImporter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->numberFormatStorage = $entityManager->getStorage('commerce_number_format');
    $this->numberFormatRepository = new NumberFormatRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function importNumberFormat(LanguageInterface $language) {
    if ($this->numberFormatStorage->load($language->getId())) {
      return FALSE;
    }

    if ($numberFormat = $this->getNumberFormat($language)) {
      $values = [
        'locale' => $numberFormat->getLocale(),
        'name' => $language->getName(),
        'numberingSystem' => $numberFormat->getNumberingSystem(),
        'decimalSeparator' => $numberFormat->getDecimalSeparator(),
        'groupingSeparator' => $numberFormat->getGroupingSeparator(),
        'plusSign' => $numberFormat->getPlusSign(),
        'minusSign' => $numberFormat->getMinusSign(),
        'percentSign' => $numberFormat->getPercentSign(),
        'decimalPattern' => $numberFormat->getDecimalPattern(),
        'percentPattern' => $numberFormat->getPercentPattern(),
        'currencyPattern' => $numberFormat->getCurrencyPattern(),
        'accountingCurrencyPattern' => $numberFormat->getAccountingCurrencyPattern(),
      ];
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
      $numberFormat = $this->numberFormatRepository->get($language->getId());
    }
    catch (UnknownLocaleException $e) {
      return FALSE;
    }
    return $numberFormat;
  }
}
