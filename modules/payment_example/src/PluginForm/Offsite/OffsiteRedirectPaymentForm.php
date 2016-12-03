<?php

namespace Drupal\commerce_payment_example\PluginForm\Offsite;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_payment\PluginForm\OffsitePaymentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class OffsiteRedirectPaymentForm extends OffsitePaymentForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\OffsiteRedirectExampleInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $payment->getOrder();
    $mode = $payment_gateway_plugin->getConfiguration()['method'];

    if ($mode == 'redirect_post') {
      $form['cancel'] = [
        '#type' => 'hidden',
        '#value' => $payment_gateway_plugin->getRedirectCancelUrl($order)->toString(),
      ];
      $form['return'] = [
        '#type' => 'hidden',
        '#value' => $payment_gateway_plugin->getRedirectReturnUrl($order)->toString(),
      ];
      $form['total'] = [
        '#type' => 'hidden',
        '#value' => $payment->getAmount()->getNumber(),
      ];
    }
    else {
      throw new NeedsRedirectException(Url::fromRoute('commerce_payment_example.dummy_redirect_302', [], [
        'absolute' => TRUE,
        'query' => [
          'cancel' => $payment_gateway_plugin->getRedirectCancelUrl($order)->toString(),
          'return' => $payment_gateway_plugin->getRedirectReturnUrl($order)->toString(),
          'total' => $payment->getAmount()->getNumber(),
        ],
      ])->toString());
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

}
