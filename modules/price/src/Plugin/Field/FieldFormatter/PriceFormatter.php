<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\field\formatter\PriceFormatter.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the commerce price formatter.
 *
 * @FieldFormatter(
 *   id = "price",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $item->amount . ' ' . $item->currency_code);
    }

    return $elements;
  }

}
