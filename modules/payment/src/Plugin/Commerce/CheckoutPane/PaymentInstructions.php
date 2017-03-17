<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment instructions pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_instructions",
 *   label = @Translation("Payment instructions"),
 *   default_step = "complete",
 *   wrapper_element = "fieldset",
 * )
 */
class PaymentInstructions extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The payment gateway is currently always required to be set.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      return [];
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    if ($payment_gateway_plugin instanceof ManualPaymentGatewayInterface && $payment_gateway_plugin->getPaymentInstructions()) {
      $pane_form += $payment_gateway_plugin->getPaymentInstructions();
      return $pane_form;
    }

    return [];
  }

}
