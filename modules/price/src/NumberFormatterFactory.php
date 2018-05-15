<?php

namespace Drupal\commerce_price;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;

@trigger_error('The ' . __NAMESPACE__ . '\NumberFormatterFactory is deprecated. Instead, use \Drupal\commerce_price\CurrencyFormatter. See http://drupal.org/node/the-change-notice-nid.', E_USER_DEPRECATED);

/**
 * Defines the NumberFormatter factory.
 *
 * @deprecated Use \Drupal\commerce_price\CurrencyFormatter instead.
 */
class NumberFormatterFactory implements NumberFormatterFactoryInterface {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new NumberFormatterFactory object.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter) {
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance() {
    return new LegacyNumberFormatter($this->currencyFormatter);
  }

}
