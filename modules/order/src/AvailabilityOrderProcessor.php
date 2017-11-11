<?php

namespace Drupal\commerce_order;

use Drupal\commerce\AvailabilityManagerInterface;
use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides an order processor that removes entities that are no longer available.
 */
class AvailabilityOrderProcessor implements OrderProcessorInterface {

  /**
   * The availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManagerInterface
   */
  protected $availabilityManager;

  /**
   * Constructs a new AvailabilityOrderProcessor object.
   *
   * @param \Drupal\commerce\AvailabilityManagerInterface $availability_manager
   *   The availability manager.
   */
  public function __construct(AvailabilityManagerInterface $availability_manager) {
    $this->availabilityManager = $availability_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // @todo Get $context as an argument to process().
    $context = new Context($order->getCustomer(), $order->getStore());
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity) {
        $available = $this->availabilityManager->check($purchased_entity, $order_item->getQuantity(), $context);
        if (!$available) {
          $order->removeItem($order_item);
          $order_item->delete();
        }
      }
    }
  }

}
