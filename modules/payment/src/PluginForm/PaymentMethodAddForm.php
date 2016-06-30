<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce\PluginForm\PluginEntityFormBase;
use Drupal\commerce_payment\CreditCard;
use Drupal\Core\Form\FormStateInterface;

class PaymentMethodAddForm extends PluginEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $form['payment_details'] = [
      '#type' => 'container',
      // Be nice to people writing form alters.
      '#payment_method_type' => $payment_method->bundle(),
    ];
    if ($payment_method->bundle() == 'credit_card') {
      $form['#payment_details']['#element_validate'][] = [
        get_class($this), 'validateCreditCardForm',
      ];
      // Build a year select list that uses a 4 digit key with a 2 digit value.
      $current_year_4 = date('Y');
      $current_year_2 = date('y');
      $years = [];
      for ($i = 0; $i < 10; $i++) {
        $years[$current_year_4 + $i] = $current_year_2 + $i;
      }

      $form['payment_details']['number'] = [
        '#type' => 'textfield',
        '#title' => t('Card number'),
        '#attributes' => ['autocomplete' => 'off'],
        '#required' => TRUE,
        '#maxlength' => 19,
        '#size' => 20,
      ];
      $form['payment_details']['expiration'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['commerce-credit-card-expiration'],
        ],
      ];
      $form['payment_details']['expiration']['month'] = [
        '#type' => 'select',
        '#title' => t('Month'),
        '#options' => [
          '01', '02', '03', '04', '05', '06',
          '07', '08', '09', '10', '11', '12'
        ],
        '#default_value' => date('m'),
        '#required' => TRUE,
      ];
      $form['payment_details']['expiration']['divider'] = [
        '#type' => 'item',
        '#title' => '',
        '#markup' => '<span class="commerce-month-year-divider">/</span>',
      ];
      $form['payment_details']['expiration']['year'] = [
        '#type' => 'select',
        '#title' => t('Year'),
        '#options' => $years,
        '#default_value' => $current_year_4,
        '#required' => TRUE,
      ];
      $form['payment_details']['security_code'] = [
        '#type' => 'textfield',
        '#title' => t('CVV'),
        '#attributes' => ['autocomplete' => 'off'],
        '#required' => TRUE,
        '#maxlength' => 4,
        '#size' => 4,
      ];
    }

    $form['billing_information'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'profile',
      '#bundle' => 'billing',
      '#default_value' => $payment_method->getBillingProfile(),
      '#save_entity' => FALSE,
    ];
    // @todo Needs to be moved to a #process, or a widget setting.
    // Remove the details wrapper from the address field.
    //if (!empty($form['address']['widget'][0])) {
    //  $form['address']['widget'][0]['#type'] = 'container';
    //}

    return $form;
  }

  /**
   * #element_validate callback: Validates the credit card form.
   */
  public function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $card_type = CreditCard::detectType($values['number']);
    if (!$card_type) {
      $form_state->setError($element['number'], '');
      return;
    }
    if (!CreditCard::validateNumber($values['number'], $card_type)) {
      $form_state->setError($element['number'], '');
    }
    if (!CreditCard::validateSecurityCode($values['security_code'], $card_type)) {
      $form_state->setError($element['security_code'], '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
   
  }

}
