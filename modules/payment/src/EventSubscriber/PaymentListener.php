<?php

namespace Drupal\commerce_payment\EventSubscriber;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentListener implements EventSubscriberInterface {

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => 'onOrderPostPlace',
    ];
  }

  /**
   * Responds to commerce_order.place.post_transition workflow transition event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onOrderPostPlace(WorkflowTransitionEvent $event) {
    drupal_set_message('trying to create payment');
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $event->getEntity();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethod $payment_method */
    $payment_method = $order->payment_method->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $payment_method->getPaymentGateway();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    // @todo Restict payment creation by gateway type / configuration?
    // if (get_class($payment_gateway_plugin) instanceof OnsitePaymentGatewayInterface == FALSE) {
    //  return;
    // }

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
    $config = $payment_gateway_plugin->getConfiguration();

    /** @var \Drupal\commerce_price\Price $amount */
    // @todo Get orderBalance()?
    // @see https://github.com/drupalcommerce/commerce/pull/508
    $amount = $order->getTotalPrice();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'state' => 'new',
      'amount' => $amount,
      'payment_gateway' => $payment_gateway->getPluginId(),
      'payment_method' => $payment_method,
      'order_id' => $order->getOrderNumber(),
    ]);

    // @todo Determine capture somehow?
    // transaction_type currently does not exist in the payment gateway config.
    // transaction_type comes from the payment capture form submit handler.
    $capture = TRUE; // ($config['transaction_type'] == 'capture');
    $payment_gateway_plugin->createPayment($payment, $capture);
  }

}
