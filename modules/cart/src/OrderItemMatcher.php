<?php

namespace Drupal\commerce_cart;

use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default implementation of the order item matcher.
 */
class OrderItemMatcher implements OrderItemMatcherInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new OrderItemMatcher object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function match(OrderItemInterface $order_item, array $order_items) {
    $order_items = $this->matchAll($order_item, $order_items);
    return count($order_items) ? $order_items[0] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function matchAll(OrderItemInterface $order_item, array $order_items) {
    $purchased_entity = $order_item->getPurchasedEntity();
    if (empty($purchased_entity)) {
      // Don't support combining order items without a purchased entity.
      return [];
    }

    $comparison_fields = ['type', 'purchased_entity'];
    $event = new OrderItemComparisonFieldsEvent($comparison_fields, $order_item);
    $this->eventDispatcher->dispatch(CartEvents::ORDER_ITEM_COMPARISON_FIELDS, $event);
    $comparison_fields = $event->getComparisonFields();

    $matched_order_items = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $existing_order_item */
    foreach ($order_items as $existing_order_item) {
      foreach ($comparison_fields as $comparison_field) {
        if (!$existing_order_item->hasField($comparison_field) || !$order_item->hasField($comparison_field)) {
          // The field is missing on one of the order items.
          continue 2;
        }
        if ($existing_order_item->get($comparison_field)->getValue() !== $order_item->get($comparison_field)->getValue()) {
          // Order item doesn't match.
          continue 2;
        }
      }
      $matched_order_items[] = $existing_order_item;
    }

    return $matched_order_items;
  }

}
