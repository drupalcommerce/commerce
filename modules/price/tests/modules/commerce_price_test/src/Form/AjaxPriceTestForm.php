<?php

namespace Drupal\commerce_price_test\Form;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form for testing AJAX on commerce_price elements.
 */
class AjaxPriceTestForm extends FormBase {

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
      '#ajax' => [
        'callback' => [$this, 'ajaxSubmit'],
        'wrapper' => 'ajax-replace',
      ],
    ];
    $form['ajax_info'] = [
      '#prefix' => '<div id="ajax-replace">',
      '#markup' => 'waiting',
      '#suffix' => '</div>',
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
    $values = $form_state->getValues();
    $price = Price::fromArray($values['amount']);
    $this->messenger()->addMessage(t('The number is "@number" and the currency code is "@currency_code".', [
      '@number' => $price->getNumber(),
      '@currency_code' => $price->getCurrencyCode(),
    ]));
  }

  /**
   * An AJAX callback for the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Some markup.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    return [
      '#prefix' => '<div id="ajax-replace">',
      '#markup' => 'AJAX successful: ' . $form_state->getTriggeringElement()['#name'],
      '#suffix' => '</div>',
    ];
  }

}
