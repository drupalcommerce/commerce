<?php

namespace Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the Offsite Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "example_offsite_redirect",
 *   label = "Example Offsite (Redirect)",
 *   display_label = "Example Offsite (Redirect)",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_example\PluginForm\Offsite\OffsiteRedirectPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class OffsiteRedirectExample extends OffsitePaymentGatewayBase implements OffsiteRedirectExampleInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'redirect_post',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method'),
      '#options' => [
        'redirect_post' => $this->t('Redirect via POST'),
        'redirect_302' => $this->t('Redirect via 302 header'),
      ],
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
      $this->configuration['method'] = $values['method'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onRedirectReturn(OrderInterface $order) {
    $current_request = \Drupal::getContainer()->get('request_stack')->getCurrentRequest();
    // Create the payment.
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      // Gateway plugins cannot reach their matching config entity directly.
      'payment_gateway' => $order->payment_gateway->entity->id(),
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $current_request->query->get('txn_id'),
      'remote_state' => $current_request->query->get('payment_status'),
      'authorized' => REQUEST_TIME,
    ]);
    $payment->save();
    drupal_set_message('Payment was processed');
  }

  /**
   * {@inheritdoc}
   */
  public function onRedirectCancel(OrderInterface $order) {
    drupal_set_message('Payment was cancelled.', 'warning');
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    // If using a 302 method most gateways require you to do some kind of
    // "handshake" procedure to send them order data and they return a unique
    // URL. So we only return one for POST methods.
    if ($this->configuration['method'] == 'redirect_post') {
      return Url::fromRoute('commerce_payment_example.dummy_redirect_post')->toString();
    }
  }

}
