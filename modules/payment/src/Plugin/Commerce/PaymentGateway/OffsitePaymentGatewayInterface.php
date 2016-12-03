<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the base interface for off-site payment gateways.
 */
interface OffsitePaymentGatewayInterface extends PaymentGatewayInterface {

  /**
   * Gets the URL for offiste redirect cancel.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\Core\Url
   *   The Url object
   */
  public function getRedirectCancelUrl(OrderInterface $order);

  /**
   * Gets the URL for offiste redirect return.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\Core\Url
   *   The Url object
   */
  public function getRedirectReturnUrl(OrderInterface $order);

  /**
   * Invoked when the off-site payment return.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function onRedirectReturn(OrderInterface $order);

  /**
   * Invoked when the off-site payment way cancelled or failed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function onRedirectCancel(OrderInterface $order);

}
