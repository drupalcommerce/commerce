<?php

namespace Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "example_offsite_redirect",
 *   label = "Example (Off-site redirect)",
 *   display_label = "Example",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_example\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase implements OffsiteInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'redirect_method' => 'post',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // A real gateway would always know which redirect method should be used,
    // it's made configurable here for test purposes.
    $form['redirect_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect method'),
      '#options' => [
        'get' => $this->t('Redirect via GET (302 header)'),
        'post' => $this->t('Redirect via POST'),
      ],
      '#default_value' => $this->configuration['redirect_method'],
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
      $this->configuration['redirect_method'] = $values['redirect_method'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // For payment gateways implementing SupportsStoredPaymentMethodsInterface,
    // we should update the stored payment method with details received
    // from the offsite redirect.
    if ($this instanceof SupportsStoredPaymentMethodsInterface) {
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $order->payment_method->entity;
      $payment_method->card_type = strtolower($request->query->get('CARD_BRAND'));
      $payment_method->card_number = substr($request->query->get('CARD_NUMBER'), -4);
      $payment_method->card_exp_month = $request->query->get('CARD_EXPIRY_MONTH');
      $payment_method->card_exp_year = $request->query->get('CARD_EXPIRY_YEAR');
      $expires = CreditCard::calculateExpirationTimestamp($request->query->get('CARD_EXPIRY_MONTH'), $request->query->get('CARD_EXPIRY_YEAR'));
      $payment_method->setExpiresTime($expires);
      $payment_method->save();
    }

    // @todo Add examples of request validation.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $request->query->get('txn_id'),
      'remote_state' => $request->query->get('payment_status'),
      'authorized' => REQUEST_TIME,
    ]);
    $payment->save();

    drupal_set_message('Payment was processed');
  }

}
