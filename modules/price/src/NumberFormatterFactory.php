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
   * @param \Drupal\commerce\LocaleContextInterface $localeContext
   *   The locale context.
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $numberFormatRepository
   *   The number format repository..
   */
  public function __construct(LocaleContextInterface $localeContext, NumberFormatRepositoryInterface $numberFormatRepository) {
    $this->localeContext = $localeContext;
    $this->numberFormatRepository = $numberFormatRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($style = NumberFormatter::CURRENCY) {
    $locale = $this->localeContext->getLocale();
    $numberFormat = $this->numberFormatRepository->get($locale);

    return new NumberFormatter($numberFormat, $style);
  }

}
