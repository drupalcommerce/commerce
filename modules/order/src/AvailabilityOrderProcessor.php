<?php

namespace Drupal\commerce_order;

use Drupal\commerce\AvailabilityManagerInterface;
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
    foreach ($order->getLineItems() as $line_item) {
      $purchased_entity = $line_item->getPurchasedEntity();
      if ($purchased_entity) {
        $available = $this->availabilityManager->check($line_item->getPurchasedEntity(), $line_item->getQuantity());
        if (!$available) {
          $order->removeLineItem($line_item);
          $line_item->delete();
        }
      }
    }
  }

}
