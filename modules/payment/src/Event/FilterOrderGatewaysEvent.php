<?php

namespace Drupal\commerce_payment\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

class FilterOrderGatewaysEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The gateways.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[]
   */
  protected $gateways;

  /**
   * Constructs a new FilterOrderGatewaysEvent object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $gateways
   *   The gateways.
   */
  public function __construct(OrderInterface $order, array $gateways) {
    $this->order = $order;
    $this->gateways = $gateways;
  }

  /**
   * Gets the event's order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the event's gateways.
   *
   * @return array|\Drupal\commerce_payment\Entity\PaymentGatewayInterface[]
   *   The gateways.
   */
  public function &getGateways() {
    return $this->gateways;
  }

  /**
   * Sets the event's gateways.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $gateways
   *   The gateways.
   *
   * @return $this
   */
  public function setGateways(array $gateways) {
    $this->gateways = $gateways;
    return $this;
  }

}
