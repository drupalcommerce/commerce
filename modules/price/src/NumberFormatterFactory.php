<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatterFactory.
 */

namespace Drupal\commerce_price;

use Drupal\commerce\LocaleContextInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use CommerceGuys\Intl\Formatter\NumberFormatter;

/**
 * Defines the NumberFormatter factory.
 */
class NumberFormatterFactory implements NumberFormatterFactoryInterface {

  /**
   * The locale context.
   *
   * @var \Drupal\commerce\LocaleContextInterface
   */
  protected $localeContext;

  /**
   * The number format repository.
   *
   * @var \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface
   */
  protected $numberFormatRepository;

  /**
   * Constructs a new NumberFormatterFactory object.
   *
   * @param \Drupal\commerce\LocaleContextInterface $locale_context
   *   The locale context.
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $number_format_repository
   *   The number format repository..
   */
  public function __construct(LocaleContextInterface $locale_context, NumberFormatRepositoryInterface $number_format_repository) {
    $this->localeContext = $locale_context;
    $this->numberFormatRepository = $number_format_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($style = NumberFormatter::CURRENCY) {
    $locale = $this->localeContext->getLocale();
    $number_format = $this->numberFormatRepository->get($locale);

    return new NumberFormatter($number_format, $style);
  }

}
