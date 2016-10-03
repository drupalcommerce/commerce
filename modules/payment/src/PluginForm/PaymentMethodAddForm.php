<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\Profile;

class PaymentMethodAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function getErrorElement(array $form, FormStateInterface $form_state) {
    return $form['payment_details'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $form['#attached']['library'][] = 'commerce_payment/payment_method_form';
    $form['#tree'] = TRUE;
    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => $payment_method->bundle(),
    ];
    if ($payment_method->bundle() == 'credit_card') {
      $form['payment_details'] = $this->buildCreditCardForm($form['payment_details'], $form_state);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      $form['payment_details'] = $this->buildPayPalForm($form['payment_details'], $form_state);
    }

    $form['billing_information'] = [
      '#parents' => array_merge($form['#parents'], ['billing_information']),
      '#type' => 'container',
    ];
    $form['billing_information'] = $this->buildBillingProfileForm($form['billing_information'], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'credit_card') {
      $this->validateCreditCardForm($form['payment_details'], $form_state);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      $this->validatePayPalForm($form['payment_details'], $form_state);
    }
    $this->validateBillingProfileForm($form['billing_information'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'credit_card') {
      $this->submitCreditCardForm($form['payment_details'], $form_state);
    }
    elseif ($payment_method->bundle() == 'paypal') {
      $this->submitPayPalForm($form['payment_details'], $form_state);
    }
    $this->submitBillingProfileForm($form['billing_information'], $form_state);

    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    // The payment method form is customer facing. For security reasons
    // the returned errors need to be more generic.
    try {
      $payment_gateway_plugin->createPaymentMethod($payment_method, $values['payment_details']);
    }
    catch (DeclineException $e) {
      \Drupal::logger('commerce_payment')->warning($e->getMessage());
      throw new DeclineException('We encountered an error processing your payment method. Please verify your details and try again.');
    }
    catch (PaymentGatewayException $e) {
      \Drupal::logger('commerce_payment')->error($e->getMessage());
      throw new PaymentGatewayException('We encountered an unexpected error processing your payment method. Please try again later.');
    }
  }

  /**
   * Builds the credit card form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built credit card form.
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
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

    $element['#attributes']['class'][] = 'credit-card-form';
    // Placeholder for the detected card type. Set by validateCreditCardForm().
    $element['type'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => t('Card number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 19,
      '#size' => 20,
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
      '#default_value' => date('m'),
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
      '#default_value' => $current_year_4,
      '#required' => TRUE,
    ];
    $element['security_code'] = [
      '#type' => 'textfield',
      '#title' => t('CVV'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
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
    $card_type = CreditCard::detectType($values['number']);
    if (!$card_type) {
      $form_state->setError($element['number'], t('You have entered a credit card number of an unsupported card type.'));
      return;
    }
    if (!CreditCard::validateNumber($values['number'], $card_type)) {
      $form_state->setError($element['number'], t('You have entered an invalid credit card number.'));
    }
    if (!CreditCard::validateExpirationDate($values['expiration']['month'], $values['expiration']['year'])) {
      $form_state->setError($element['expiration'], t('You have entered an expired credit card.'));
    }
    if (!CreditCard::validateSecurityCode($values['security_code'], $card_type)) {
      $form_state->setError($element['security_code'], t('You have entered an invalid CVV.'));
    }

    // Persist the detected card type.
    $form_state->setValueForElement($element['type'], $card_type->getId());
  }

  /**
   * Handles the submission of the credit card form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $this->entity->card_type = $values['type'];
    $this->entity->card_number = substr($values['number'], -4);
    $this->entity->card_exp_month = $values['expiration']['month'];
    $this->entity->card_exp_year = $values['expiration']['year'];
  }

  /**
   * Builds the PayPal form.
   *
   * Empty by default because there is no generic PayPal form, it's always
   * payment gateway specific (and usually JS based).
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built credit card form.
   */
  protected function buildPayPalForm(array $element, FormStateInterface $form_state) {
    // Placeholder for the PayPal mail.
    $element['paypal_mail'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    return $element;
  }

  /**
   * Validates the PayPal form.
   *
   * @param array $element
   *   The PayPal form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validatePayPalForm(array &$element, FormStateInterface $form_state) {}

  /**
   * Handles the submission of the PayPal form.
   *
   * @param array $element
   *   The PayPal form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitPayPalForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $this->entity->paypal_mail = $values['paypal_mail'];
  }

  /**
   * Builds the billing profile form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built billing profile form.
   */
  protected function buildBillingProfileForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = Profile::create([
      'type' => 'customer',
      'uid' => $payment_method->getOwnerId(),
    ]);
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->buildForm($billing_profile, $element, $form_state);
    // Remove the details wrapper from the address field.
    if (!empty($element['address']['widget'][0])) {
      $element['address']['widget'][0]['#type'] = 'container';
    }
    // Store the billing profile for the validate/submit methods.
    $element['#entity'] = $billing_profile;

    return $element;
  }

  /**
   * Validates the billing profile form.
   *
   * @param array $element
   *   The billing profile form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validateBillingProfileForm(array &$element, FormStateInterface $form_state) {
    $billing_profile = $element['#entity'];
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->extractFormValues($billing_profile, $element, $form_state);
    $form_display->validateFormValues($billing_profile, $element, $form_state);
  }

  /**
   * Handles the submission of the billing profile form.
   *
   * @param array $element
   *   The billing profile form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitBillingProfileForm(array $element, FormStateInterface $form_state) {
    $billing_profile = $element['#entity'];
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->extractFormValues($billing_profile, $element, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_method->setBillingProfile($billing_profile);
  }

}
