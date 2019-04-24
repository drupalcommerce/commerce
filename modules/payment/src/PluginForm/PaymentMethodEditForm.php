<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Form\FormStateInterface;

class PaymentMethodEditForm extends PaymentMethodFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'credit_card') {
      $form['payment_details'] = $this->buildCreditCardForm($payment_method, $form_state);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      // @todo Decide how to handle saved PayPal payment methods.
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'credit_card') {
      $this->validateCreditCardForm($form['payment_details'], $form_state);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      // @todo Decide how to handle saved PayPal payment methods.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'credit_card') {
      $expiration_date = $form_state->getValue(['payment_method', 'payment_details', 'expiration']);
      $payment_method->get('card_exp_month')->setValue($expiration_date['month']);
      $payment_method->get('card_exp_year')->setValue($expiration_date['year']);
      $expires = CreditCard::calculateExpirationTimestamp($expiration_date['month'], $expiration_date['year']);
      $payment_method->setExpiresTime($expires);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      // @todo Decide how to handle saved PayPal payment methods.
    }

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsUpdatingStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    // The payment method form is customer facing. For security reasons
    // the returned errors need to be more generic.
    try {
      $payment_gateway_plugin->updatePaymentMethod($payment_method);
      $payment_method->save();
    }
    catch (DeclineException $e) {
      $this->logger->warning($e->getMessage());
      throw new DeclineException(t('We encountered an error processing your payment method. Please verify your details and try again.'));
    }
    catch (PaymentGatewayException $e) {
      $this->logger->error($e->getMessage());
      throw new PaymentGatewayException(t('We encountered an unexpected error processing your payment method. Please try again later.'));
    }
  }

  /**
   * Builds the credit card form.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built credit card form.
   */
  protected function buildCreditCardForm(PaymentMethodInterface $payment_method, FormStateInterface $form_state) {
    // Build a month select list that shows months with a leading zero.
    $months = [];
    for ($i = 1; $i < 13; $i++) {
      $month = str_pad($i, 2, '0', STR_PAD_LEFT);
      $months[$month] = $month;
    }
    // Build a year select list that uses a 4 digit key with a 2 digit value.
    $current_year_4 = date('Y');
    $current_year_2 = date('y');
    $years = [];
    for ($i = 0; $i < 10; $i++) {
      $years[$current_year_4 + $i] = $current_year_2 + $i;
    }

    $element['#attached']['library'][] = 'commerce_payment/payment_method_icons';
    $element['#attributes']['class'][] = 'credit-card-form';
    $element['type'] = [
      '#type' => 'hidden',
      '#value' => $payment_method->get('card_type')->value,
    ];
    $element['number'] = [
      '#type' => 'inline_template',
      '#template' => '<span class="payment-method-icon payment-method-icon--{{ type }}"></span>{{ label }}',
      '#context' => [
        'type' => $payment_method->get('card_type')->value,
        'label' => $payment_method->label(),
      ],
    ];
    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['credit-card-form__expiration'],
      ],
    ];
    $element['expiration']['month'] = [
      '#type' => 'select',
      '#title' => t('Month'),
      '#options' => $months,
      '#default_value' => str_pad($payment_method->get('card_exp_month')->value, 2, '0', STR_PAD_LEFT),
      '#required' => TRUE,
    ];
    $element['expiration']['divider'] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<span class="credit-card-form__divider">/</span>',
    ];
    $element['expiration']['year'] = [
      '#type' => 'select',
      '#title' => t('Year'),
      '#options' => $years,
      '#default_value' => $payment_method->get('card_exp_year')->value,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Validates the credit card form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    if (!CreditCard::validateExpirationDate($values['expiration']['month'], $values['expiration']['year'])) {
      $form_state->setError($element['expiration'], t('You have entered an expired credit card.'));
    }
  }

}
