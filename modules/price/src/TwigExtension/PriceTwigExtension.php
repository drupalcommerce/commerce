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
    return 'commerce_price.twig_extension';
  }

  /**
   * Formats a price object/array.
   *
   * Examples:
   * {{ order.getTotalPrice|commerce_price_format }}
   * {{ order.getTotalPrice|commerce_price_format|default('N/A') }}
   * {{ order.getTotalPrice|commerce_price_format({'minimum_fraction_digits': 0}) }}
   * {{ order.getTotalPrice|commerce_price_format({'currency_display': 'code''}) }}
   *
   * @param mixed $price
   *   Either a Price object, or an array with number and currency_code keys.
   * @param array $options
   *   (optional) An array of options to pass to the currency formatter.
   *
   * @return mixed
   *   A formatted price, suitable for rendering in a twig template.
   *
   * @throws \InvalidArgumentException
   */
  public static function formatPrice($price, array $options = []) {
    if (empty($price)) {
      return '';
    }

    if ($price instanceof Price) {
      $price = $price->toArray();
    }
    if (is_array($price) && isset($price['currency_code']) && isset($price['number'])) {
      $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
      return $currency_formatter->format($price['number'], $price['currency_code'], $options);
    }
    else {
      throw new \InvalidArgumentException('The "commerce_price_format" filter must be given a price object or an array with "number" and "currency_code" keys.');
    }
  }

}
