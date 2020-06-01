<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AvailabilityOrderProcessor object.
   *
   * @param \Drupal\commerce_order\AvailabilityManagerInterface $availability_manager
   *   The availability manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AvailabilityManagerInterface $availability_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->availabilityManager = $availability_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // @todo Get $context as an argument to process().
    $context = new Context($order->getCustomer(), $order->getStore());
    $order_items_to_remove = [];
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity) {
        continue;
      }
      $availability_result = $this->availabilityManager->check($order_item, $context);
      if ($availability_result->isUnavailable()) {
        // We collect the order item ids to remove here instead of directly
        // calling $order->removeItem(), mostly for performance reasons since
        // that allows us to remove multiple order items at a time and
        // recalculate the order total only once.
        $order_items_to_remove[$order_item->id()] = $order_item;
      }
    }
    if (!$order_items_to_remove) {
      return;
    }
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $order_item_ids = array_keys($order_items_to_remove);
    $order->get('order_items')->filter(function ($item) use ($order_item_ids) {
      return !in_array($item->target_id, $order_item_ids);
    });
    $order_item_storage->delete($order_items_to_remove);
    // Since we don't call removeItem(), we manually have to recalculate the
    // order total.
    $order->recalculateTotalPrice();
  }

}
