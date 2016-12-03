<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsiteRedirectPaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class OffsitePaymentForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    if ($payment_gateway_plugin instanceof OffsiteRedirectPaymentGatewayInterface) {
      $form['#attached']['library'][] = 'commerce_payment/offsite_redirect';
      $form['help'] = [
        '#markup' => '<div class="checkout-help">' . t('Please wait while you are redirected to the payment server. If nothing happens within 10 seconds, please click on the button below.') . '</div>',
        '#weight' => -10,
      ];
    }

    // Manually set parents so all of the off-site redirect inputs are on
    // the root of the form.
    foreach (Element::children($form) as $child) {
      $form[$child]['#parents'] = [$child];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing. Off-site payment gateways do not submit forms to Drupal.
  }

}
