<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the base class for on-site payment gateways.
 */
abstract class OnsitePaymentGatewayBase extends PaymentGatewayBase implements OnsitePaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // The PaymentInformation pane uses payment method labels
    // for on-site gateways, the display label is unused.
    $form['display_label']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultForms() {
    return [
      'add-payment' => 'Drupal\commerce_payment\PluginForm\OnsitePaymentAddForm',
    ] + parent::getDefaultForms();
  }

}
