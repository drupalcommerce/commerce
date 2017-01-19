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
        $response = $this->availabilityManager->check($purchased_entity, $order_item->getQuantity(), $context);
        if ($response->getMax() == 0) {
          $order->removeItem($order_item);
          $order_item->delete();
          drupal_set_message(t('The item %item is no longer available and has been removed from your order.', [
            '%item' => $order_item->getTitle(),
          ]));
        }
        elseif ($response->getMax() < $order_item->getQuantity()) {
          $order_item->setQuantity($response->getMax());
          $order_item->save();
          drupal_set_message(t('The item %item is no longer available in the quantity you selected. Your order has been updated to reflect the new availability level.', [
            '%item' => $order_item->getTitle(),
          ]));
        }
      }
    }
  }

}
