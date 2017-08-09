<?php

namespace Drupal\commerce_price\TwigExtension;

use Drupal\commerce_price\Price;

/**
 * Provides Price-specific Twig extensions.
 */
class PriceTwigExtension extends \Twig_Extension {

  /**
   * @inheritdoc
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('commerce_price_format', [$this, 'formatPrice']),
    ];
  }

  /**
   * @inheritdoc
   */
  public function getName() {
    return 'commerce_price.twig_extenstion';
  }

  /**
   * Formats a price object/array.
   *
   * Example: {{ order.getTotalPrice|commerce_price_format }}
   *
   * @param mixed $price
   *   Either a Price object, or an array with number and currency_code keys.
   *
   * @return mixed
   *   A formatted price, suitable for rendering in a twig template.
   *
   * @throws \InvalidArgumentException
   */
  public static function formatPrice($price) {
    if (is_array($price) && isset($price['currency_code']) && isset($price['number'])) {
      $price = new Price($price['number'], $price['currency_code']);
    }
    elseif (!$price instanceof Price) {
      throw new \InvalidArgumentException('The "commerce_price_format" filter must be given a price object or an array with "number" and "currency_code" keys.');
    }

    /** @var \Drupal\commerce_price\Rounder $rounder */
    $rounder = \Drupal::service('commerce_price.rounder');
    $price = $rounder->round($price);
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance();
    $currency_storage = \Drupal::entityTypeManager()->getStorage('commerce_currency');
    $currency = $currency_storage->load($price->getCurrencyCode());
    return $number_formatter->formatCurrency($price->getNumber(), $currency);
  }

}
