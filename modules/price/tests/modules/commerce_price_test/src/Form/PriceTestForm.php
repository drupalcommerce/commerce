<?php

namespace Drupal\commerce_price_test\Form;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PriceTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_price_element_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Amount'),
      '#default_value' => ['number' => '99.99', 'currency_code' => 'USD'],
      '#required' => TRUE,
      '#available_currencies' => ['USD', 'EUR'],
    ];
    $form['amount_hidden_title'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Hidden title amount'),
      '#title_display' => 'invisible',
      '#default_value' => ['number' => '99.99', 'currency_code' => 'USD'],
      '#required' => TRUE,
      '#available_currencies' => ['USD', 'EUR'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create a Price to ensure the values are valid.
    $value = $form_state->getValue('amount');
    $price = new Price($value['number'], $value['currency_code']);
    drupal_set_message(t('The number is "@number" and the currency code is "@currency_code".', [
      '@number' => $price->getNumber(),
      '@currency_code' => $price->getCurrencyCode(),
    ]));
  }

}
