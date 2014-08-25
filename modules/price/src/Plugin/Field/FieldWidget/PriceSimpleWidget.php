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
 * Plugin implementation of the 'link' widget.
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
    $element['amount'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => isset($items[$delta]->amount) ? $items[$delta]->amount : NULL,
      '#required' => $element['#required'],
      '#size' => 10,
      '#maxlength' => 255,
    );
    $element['currency_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#default_value' => isset($items[$delta]->currency_code) ? $items[$delta]->currency_code : NULL,
      '#required' => $element['#required'],
      '#maxlength' => 3,
      '#size' => 3,
    );

    return $element;
  }

}
