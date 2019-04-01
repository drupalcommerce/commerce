<?php

namespace Drupal\commerce_payment;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the payment storage.
 */
class PaymentStorage extends CommerceContentEntityStorage implements PaymentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByRemoteId($remote_id) {
    $payments = $this->loadByProperties(['remote_id' => $remote_id]);
    $payment = reset($payments);

    return $payment ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByOrder(OrderInterface $order) {
    $query = $this->getQuery()
      ->condition('order_id', $order->id())
      ->sort('payment_id');
    $result = $query->execute();

    return $result ? $this->loadMultiple($result) : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // Populate the type using the payment gateway.
    if (!isset($values['type']) && !empty($values['payment_gateway'])) {
      $payment_gateway = $values['payment_gateway'];
      if (is_string($payment_gateway)) {
        // The caller passed tha payment gateway ID, load the full entity.
        $payment_gateway_storage = $this->entityManager->getStorage('commerce_payment_gateway');
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
        $payment_gateway = $payment_gateway_storage->load($payment_gateway);
      }
      $payment_type = $payment_gateway->getPlugin()->getPaymentType();
      $values['type'] = $payment_type->getPluginId();
    }

    return parent::doCreate($values);
  }

}
