<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Manual payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "manual",
 *   label = "Manual",
 *   display_label = "Manual",
 *   payment_type = "payment_manual",
 *   payment_method_types = {"manual"},
 * )
 */
class Manual extends ManualPaymentGatewayBase implements ManualPaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'reusable' => FALSE,
      'expires' => '',
      'instructions' => [
        'value' => '',
        'format' => 'plain_text',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['manual'] = [
      '#type' => 'fieldset',
      '#title' => t('Manual payment settings'),
    ];
    $form['manual']['reusable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reusable'),
      '#description' => $this->t('Check if you want to have reusable payment methods for this gateway.'),
      '#default_value' => $this->configuration['reusable'],
    ];
    $form['manual']['expires'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expires'),
      '#description' => $this->t('An offset from the current time such as "@example1", "@example2 or "@example3". Leave empty for never expires.', ['@example1' => '1 year', '@example2' => '3 months', '@example3' => '60 days']),
      '#default_value' => $this->configuration['expires'],
      '#size' => 10,
    ];
    $form['manual']['instructions'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Instructions'),
      '#description' => $this->t('Manual payment instructions to be displayed to customer on checkout.'),
      '#default_value' => $this->configuration['instructions']['value'],
      '#format' => $this->configuration['instructions']['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $form_parents = $form['#parents'];
      $form_parents[] = 'manual';
      $values = $form_state->getValue($form_parents);
      if (!empty($values['expires'])) {
        $convert = strtotime($values['expires']);
        if ($convert == -1 || $convert === FALSE) {
          $form_state->setError($form['manual']['expires'], $this->t('Invalid offset time format.'));
        }
        if ($convert < \Drupal::service('commerce.time')->getRequestTime()) {
          $form_state->setError($form['manual']['expires'], $this->t('Future offset time is needed for Expires.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $form_parents = $form['#parents'];
      $form_parents[] = 'manual';
      $values = $form_state->getValue($form_parents);
      $this->configuration['instructions'] = $values['instructions'];
      $this->configuration['reusable'] = $values['reusable'];
      $this->configuration['expires'] = $values['expires'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    if ($payment->getState()->value != 'new') {
      throw new \InvalidArgumentException('The provided payment is in an invalid state.');
    }
    $payment_method = $payment->getPaymentMethod();
    if (empty($payment_method)) {
      throw new \InvalidArgumentException('The provided payment has no payment method referenced.');
    }
    if ($payment_method->isExpired()) {
      throw new HardDeclineException('The provided payment method has expired');
    }

    $test = $this->getMode() == 'test';
    $payment->setTest($test);
    $payment->state = 'pending';
    $payment->setAuthorizedTime(\Drupal::service('commerce.time')->getRequestTime());
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function completePayment(PaymentInterface $payment, Price $amount = NULL) {
    if ($payment->getState()->value != 'pending') {
      throw new \InvalidArgumentException('Only payments in the "authorization" state can be captured.');
    }

    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $payment->state = 'completed';
    $payment->setAmount($amount);
    $payment->setCapturedTime(\Drupal::service('commerce.time')->getRequestTime());
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function cancelPayment(PaymentInterface $payment) {
    if ($payment->getState()->value != 'pending') {
      throw new \InvalidArgumentException('Only payments in the "authorization" state can be voided.');
    }

    $payment->state = 'canceled';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    if (!in_array($payment->getState()->value, ['completed', 'partially_refunded'])) {
      throw new \InvalidArgumentException('Only payments in the "completed" and "partially_refunded" states can be refunded.');
    }
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();

    // Validate the requested amount.
    $balance = $payment->getBalance();
    if ($amount->greaterThan($balance)) {
      throw new InvalidRequestException(sprintf("Can't refund more than %s.", $balance->__toString()));
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->state = 'partially_refunded';
    }
    else {
      $payment->state = 'refunded';
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // No expected keys required for Manual payments.
    // Set expires according with configuration.
    $expires = $this->configuration['expires'] ? strtotime($this->configuration['expires']) : 0;
    // The remote ID returned by the request.
    $remote_id = $payment_method->getOwnerId();

    $payment_method->setRemoteId($remote_id);
    $payment_method->setReusable($this->configuration['reusable']);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentInstructions() {
    $element = NULL;
    $instructions = $this->configuration['instructions'];
    if (!empty($instructions['value'])) {
      $element['instructions'] = [
        '#markup' => check_markup($instructions['value'], $instructions['format']),
      ];
    }

    return $element;
  }

}
