<?php

namespace Drupal\commerce_payment_example\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $redirect_method = $payment_gateway_plugin->getConfiguration()['redirect_method'];
    if ($redirect_method == 'post') {
      $redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_post')->toString();
    }
    else {
      // Gateways that use the GET redirect method usually perform an API call
      // that prepares the remote payment and provides the actual url to
      // redirect to. Any params received from that API call that need to be
      // persisted until later payment creation can be saved in $order->data.
      // Example: $order->setData('my_gateway', ['test' => '123']), followed
      // by an $order->save().
      $redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_302', [], ['absolute' => TRUE])->toString();
    }
    $data = [
      'return' => $payment_gateway_plugin->getReturnUrl($order)->toString(),
      'cancel' => $payment_gateway_plugin->getCancelUrl($order)->toString(),
      'total' => $payment->getAmount()->getNumber(),
    ];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
