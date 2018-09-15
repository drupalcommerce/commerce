<?php

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\Formatter\CurrencyFormatter as ExternalCurrencyFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use Drupal\commerce\CurrentLocaleInterface;

/**
 * Extends the commerceguys/intl CurrencyFormatter to provide better defaults.
 *
 * @see \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
 */
class CurrencyFormatter extends ExternalCurrencyFormatter {

  /**
   * Constructs a new CurrencyFormatter object.
   *
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $number_format_repository
   *   The number format repository.
   * @param \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface $currency_repository
   *   The currency repository.
   * @param \Drupal\commerce\CurrentLocaleInterface $current_locale
   *   The current locale.
   */
  public function __construct(NumberFormatRepositoryInterface $number_format_repository, CurrencyRepositoryInterface $currency_repository, CurrentLocaleInterface $current_locale) {
    $default_options = [
      'locale' => $current_locale->getLocale()->getLocaleCode(),
      // Show prices as-is. All digits (storage max is 6), non-rounded.
      'maximum_fraction_digits' => 6,
      'rounding_mode' => 'none',
    ];

    parent::__construct($number_format_repository, $currency_repository, $default_options);
  }

}
