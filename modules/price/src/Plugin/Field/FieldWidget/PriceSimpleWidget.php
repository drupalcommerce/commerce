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
    $enabled_currencies = entity_load_multiple_by_properties('commerce_currency', array('status' => 1));
    $currency_codes = array_keys($enabled_currencies);

    $element['amount'] = array(
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => isset($items[$delta]->amount) ? $items[$delta]->amount : NULL,
      '#required' => $element['#required'],
      '#size' => 10,
      '#maxlength' => 255,
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'commerce_price') . '/css/commerce_price.css',
        ),
      ),
    );
    $element['currency_code'] = array(
      '#type' => 'select',
      '#title' => $this->t('Currency code'),
      '#default_value' => isset($items[$delta]->currency_code) ? $items[$delta]->currency_code : NULL,
      '#required' => $element['#required'],
      '#options' => array_combine($currency_codes, $currency_codes),
      '#title_display' => 'invisible',
    );

    return $element;
  }

}
