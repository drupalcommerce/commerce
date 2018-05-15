<?php

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Entity\CurrencyInterface;

/**
 * Provides a legacy number formatter for the deprecated NumberFormatterFactory.
 *
 * Wraps the new CurrencyFormatter for compatibility with old code.
 */
class LegacyNumberFormatter {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The formatting options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructs a new LegacyNumberFormatter object.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter) {
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * Formats a number.
   *
   * @param string $number
   *   The number.
   *
   * @return string
   *   The formatted number
   */
  public function format($number) {
    $options = ['currency_display' => 'none'] + $this->options;
    return $this->currencyFormatter->format($number, 'XXX', $options);
  }

  /**
   * Formats a currency amount.
   *
   * @param string $number
   *   The number.
   * @param \Drupal\commerce_price\Entity\CurrencyInterface $currency
   *   The currency.
   *
   * @return string
   *   The formatted currency amount.
   */
  public function formatCurrency($number, CurrencyInterface $currency) {
    return $this->currencyFormatter->format($number, $currency->id(), $this->options);
  }

  /**
   * Parses a formatted number.
   *
   * @param string $number
   *   The formatted number.
   *
   * @return string|false
   *   The parsed number, or FALSE on failure.
   */
  public function parse($number) {
    return $this->currencyFormatter->parse($number, 'XXX');
  }

  /**
   * Parses a formatted currency amount.
   *
   * @param string $number
   *   The formatted number.
   * @param \Drupal\commerce_price\Entity\CurrencyInterface $currency
   *   The currency.
   *
   * @return string|false
   *   The parsed currency amount, or FALSE on failure.
   */
  public function parseCurrency($number, CurrencyInterface $currency) {
    return $this->currencyFormatter->parse($number, $currency->id());
  }

  /**
   * Sets the minimum number of fraction digits.
   *
   * @param int $minimum_fraction_digits
   *   The minimum number of fraction digits.
   *
   * @return $this
   */
  public function setMinimumFractionDigits($minimum_fraction_digits) {
    $this->options['minimum_fraction_digits'] = $minimum_fraction_digits;
    return $this;
  }

  /**
   * Sets the maximum number of fraction digits.
   *
   * @param int $maximum_fraction_digits
   *   The maximum number of fraction digits.
   *
   * @return $this
   */
  public function setMaximumFractionDigits($maximum_fraction_digits) {
    $this->options['maximum_fraction_digits'] = $maximum_fraction_digits;
    return $this;
  }

  /**
   * Sets whether grouping is used.
   *
   * @param bool $grouping_used
   *   Whether grouping is used.
   *
   * @return $this
   */
  public function setGroupingUsed($grouping_used) {
    $this->options['use_grouping'] = $grouping_used;
    return $this;
  }

  /**
   * Sets the currency display.
   *
   * Allowed values: 'symbol', 'code', 'none'.
   *
   * @param string $currency_display
   *   The currency display.
   *
   * @return $this
   */
  public function setCurrencyDisplay($currency_display) {
    $this->options['currency_display'] = $currency_display;
    return $this;
  }

}
