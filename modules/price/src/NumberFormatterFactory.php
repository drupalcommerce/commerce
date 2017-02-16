<?php

namespace Drupal\commerce_price;

use Drupal\commerce\CurrentLocaleInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use CommerceGuys\Intl\Formatter\NumberFormatter;

/**
 * Defines the NumberFormatter factory.
 */
class NumberFormatterFactory implements NumberFormatterFactoryInterface {

  /**
   * The current locale.
   *
   * @var \Drupal\commerce\CurrentLocaleInterface
   */
  protected $currentLocale;

  /**
   * The number format repository.
   *
   * @var \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface
   */
  protected $numberFormatRepository;

  /**
   * Constructs a new NumberFormatterFactory object.
   *
   * @param \Drupal\commerce\CurrentLocaleInterface $current_locale
   *   The current locale.
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $number_format_repository
   *   The number format repository..
   */
  public function __construct(CurrentLocaleInterface $current_locale, NumberFormatRepositoryInterface $number_format_repository) {
    $this->currentLocale = $current_locale;
    $this->numberFormatRepository = $number_format_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($style = NumberFormatter::CURRENCY) {
    $locale = $this->currentLocale->getLocale();
    $number_format = $this->numberFormatRepository->get($locale);

    return new NumberFormatter($number_format, $style);
  }

}
