<?php

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Formatter\NumberFormatter as ExternalNumberFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use Drupal\commerce\CurrentLocaleInterface;

/**
 * Extends the commerceguys/intl NumberFormatter to provide better defaults.
 *
 * @see \CommerceGuys\Intl\Formatter\NumberFormatterInterface
 */
class NumberFormatter extends ExternalNumberFormatter {

  /**
   * Constructs a new NumberFormatter object.
   *
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $number_format_repository
   *   The number format repository.
   * @param \Drupal\commerce\CurrentLocaleInterface $current_locale
   *   The current locale.
   * @param array $default_options
   *   The default options.
   */
  public function __construct(NumberFormatRepositoryInterface $number_format_repository, CurrentLocaleInterface $current_locale, array $default_options = []) {
    $default_options += [
      'locale' => $current_locale->getLocale()->getLocaleCode(),
    ];

    parent::__construct($number_format_repository, $default_options);
  }

}
