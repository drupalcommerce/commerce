<?php

namespace Drupal\commerce_payment_test\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\OffsiteRedirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Test off-site payment gateway.
 *
 * This is a copy of example_offsite_redirect with additional logic around
 * order data usage.
 *
 * @CommercePaymentGateway(
 *   id = "test_offsite",
 *   label = "Test (Off-site redirect)",
 *   display_label = "Test",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_example\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class TestOffsite extends OffsiteRedirect {

  /**
   * {@inheritdoc}
   *
   * Adds data to the order and saves it. Done before or after the payment
   * is saved. Used by OffsiteOrderDataTest.
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $order->setData('test_offsite', ['test' => TRUE]);
    $state = \Drupal::state();

    if ($state->get('offsite_order_data_test_save') === 'before') {
      $order->save();
    }

    parent::onReturn($order, $request);

    if ($state->get('offsite_order_data_test_save') === 'after') {
      $order->save();
    }
  }

}
