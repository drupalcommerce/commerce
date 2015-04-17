<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\Field\FieldWidget\PriceSimpleWidget.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the commerce price widget.
 *
 * @FieldWidget(
 *   id = "price_simple",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceSimpleWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the enabled currencies.
    $enabledCurrencies = entity_load_multiple_by_properties('commerce_currency', ['status' => 1]);
    $currency_codes = array_keys($enabledCurrencies);

    $default_amount = NULL;
    if (isset($items[$delta]->amount)) {
      // Trim all trailing 0. Since prices doesn't use significant figures they
      // are redundant. Maybe we should keep the zeroes that normally would be
      // displayed (fx 123.00 for EUR). For now this should be enough.
      $default_amount = rtrim($items[$delta]->amount, 0);
    }
    $element['amount'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $default_amount,
      '#required' => $element['#required'],
      '#size' => 10,
      '#maxlength' => 255,
      '#attached' => [
        'library' => [
          'commerce_price/drupal.commerce_price.simple-widget',
        ],
      ],
    ];
    $element['currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency code'),
      '#default_value' => isset($items[$delta]->currency_code) ? $items[$delta]->currency_code : NULL,
      '#required' => $element['#required'],
      '#options' => array_combine($currency_codes, $currency_codes),
      '#title_display' => 'invisible',
    ];

    return $element;
  }

}
