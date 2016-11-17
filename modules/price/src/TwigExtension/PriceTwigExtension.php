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
    if ($price instanceof Price) {
      $price = $price->toArray();
    }

    if (is_array($price) && isset($price['currency_code']) && isset($price['number'])) {
      $currency_storage = \Drupal::entityTypeManager()->getStorage('commerce_currency');
      $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance();
      $currency = $currency_storage->load($price['currency_code']);
      $amount = $price['number'];
      $formatted_price = $number_formatter->formatCurrency($amount, $currency);

      return $formatted_price;
    }
    else {
      throw new \InvalidArgumentException('"commerce_price_format" filter argument must be a price object or an array with keys "number" and "currency_code".');
    }
  }

}
