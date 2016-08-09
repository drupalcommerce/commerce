<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for entities managed by a payment gateway.
 */
interface EntityWithPaymentGatewayInterface extends EntityInterface {

  /**
   * Gets the payment gateway.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null
   *   The payment gateway entity, or null if unknown.
   */
  public function getPaymentGateway();

  /**
   * Gets the payment gateway ID.
   *
   * @return int|null
   *   The payment gateway ID, or null if unknown.
   */
  public function getPaymentGatewayId();

}
