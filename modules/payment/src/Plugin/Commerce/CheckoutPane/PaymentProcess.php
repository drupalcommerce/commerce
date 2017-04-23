<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the payment process pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_process",
 *   label = @Translation("Payment process"),
 *   default_step = "payment",
 *   wrapper_element = "container",
 * )
 */
class PaymentProcess extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'capture' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['capture'])) {
      $summary = $this->t('Transaction mode: Authorize and capture');
    }
    else {
      $summary = $this->t('Transaction mode: Authorize only');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['capture'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction mode'),
      '#description' => $this->t('This setting is only respected if the chosen payment gateway supports authorizations.'),
      '#options' => [
        TRUE => $this->t('Authorize and capture'),
        FALSE => $this->t('Authorize only (requires manual capture after checkout)'),
      ],
      '#default_value' => (int) $this->configuration['capture'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['capture'] = !empty($values['capture']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // This pane can't be used without the PaymentInformation pane.
    $payment_info_pane = $this->checkoutFlow->getPane('payment_information');
    return $payment_info_pane->isVisible() && $payment_info_pane->getStepId() != '_disabled';
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The payment gateway is currently always required to be set.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      drupal_set_message($this->t('No payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'new',
      'amount' => $this->order->getTotalPrice(),
      'payment_gateway' => $payment_gateway->id(),
      'order_id' => $this->order->id(),
    ]);
    $next_step_id = $this->checkoutFlow->getNextStepId($this->getStepId());

    if ($payment_gateway_plugin instanceof OnsitePaymentGatewayInterface) {
      try {
        $payment->payment_method = $this->order->payment_method->entity;
        $payment_gateway_plugin->createPayment($payment, $this->configuration['capture']);
        $this->checkoutFlow->redirectToStep($next_step_id);
      }
      catch (DeclineException $e) {
        $message = $this->t('We encountered an error processing your payment method. Please verify your details and try again.');
        drupal_set_message($message, 'error');
        $this->redirectToPreviousStep();
      }
      catch (PaymentGatewayException $e) {
        \Drupal::logger('commerce_payment')->error($e->getMessage());
        $message = $this->t('We encountered an unexpected error processing your payment method. Please try again later.');
        drupal_set_message($message, 'error');
        $this->redirectToPreviousStep();
      }
    }
    elseif ($payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      $pane_form['offsite_payment'] = [
        '#type' => 'commerce_payment_gateway_form',
        '#operation' => 'offsite-payment',
        '#default_value' => $payment,
        '#return_url' => $this->buildReturnUrl($this->order),
        '#cancel_url' => $this->buildCancelUrl($this->order),
        '#capture' => $this->configuration['capture'],
      ];

      $complete_form['actions']['next']['#value'] = $this->t('Proceed to @gateway', [
        '@gateway' => $payment_gateway_plugin->getDisplayLabel(),
      ]);

      return $pane_form;
    }
    else {
      $this->checkoutFlow->redirectToStep($next_step_id);
    }
  }

  /**
   * Builds the URL to the "return" page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The "return" page url.
   */
  protected function buildReturnUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Builds the URL to the "cancel" page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The "cancel" page url.
   */
  protected function buildCancelUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Redirects to a previous checkout step on error.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   */
  protected function redirectToPreviousStep() {
    $payment_info_pane = $this->checkoutFlow->getPane('payment_information');
    $previous_step_id = $payment_info_pane->getStepId();
    $this->checkoutFlow->redirectToStep($previous_step_id);
  }

}
