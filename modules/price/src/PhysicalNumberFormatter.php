<?php

namespace Drupal\commerce_price;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use Drupal\commerce\CurrentLocaleInterface;
use Drupal\physical\NumberFormatterInterface;

/**
 * Wraps the NumberFormatter for use by the Physical Fields module.
 *
 * @see \Drupal\commerce_price\CommercePriceServiceProvider
 */
class PhysicalNumberFormatter extends NumberFormatter implements NumberFormatterInterface {

  /**
   * Constructs a new PhysicalNumberFormatter object.
   *
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $number_format_repository
   *   The number format repository.
   * @param \Drupal\commerce\CurrentLocaleInterface $current_locale
   *   The current locale.
   */
  public function __construct(NumberFormatRepositoryInterface $number_format_repository, CurrentLocaleInterface $current_locale) {
    $default_options = [
      'maximum_fraction_digits' => 6,
    ];

    parent::__construct($number_format_repository, $current_locale, $default_options);
  }

}
