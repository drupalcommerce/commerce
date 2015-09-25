<?php
/**
 * @file
 * Contains \Drupal\commerce_order\LineItemMatcher.
 */

namespace Drupal\commerce_order;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\LineItemComparisonFieldsEvent;

/**
 * Class LineItemMatcher
 *
 * Match an existing line item for the PurchasableEntity.
 *
 * @package Drupal\commerce_order
 */
class LineItemMatcher implements LineItemMatcherInterface {

  /**
   * An Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $event_dispatcher;

  /**
   * Constructs a new LineItemMatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->event_dispatcher = $event_dispatcher;
  }


  /**
   * {@inheritdoc}
   */
  public function match(PurchasableEntityInterface $entity, OrderInterface $order) {
    $matchedLineItems = $this->matchAll($entity, $order);
    if (is_array($matchedLineItems)) {
      return $matchedLineItems[0];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function matchAll(PurchasableEntityInterface $entity, OrderInterface $order) {

    // Get the comparison fields from all modules altering them.
    $event = new LineItemComparisonFieldsEvent($entity, ['type', 'id']);
    $this->event_dispatcher->dispatch(OrderEvents::LINE_ITEM_COMPARISON_FIELDS, $event);
    $comparison_fields = $event->getComparisonFields();

    // Try to match line items.
    $matched_line_items = [];
    foreach ($order->getLineItems() as $lineItem) {
      foreach ($comparison_fields as $comparison_field) {
        if (!$lineItem->getPurchasedEntity()->hasField($comparison_field) || !$entity->hasField($comparison_field)) {
          // Continue the loop with the next field.
          continue;
        }
        if ($lineItem->getPurchasedEntity()->get($comparison_field) !== $entity->get($comparison_field)) {
          // Continue the parent loop with the next line item.
          continue 2;
        }
      }
      $matched_line_items[] = $lineItem;
    }
    return is_array($matched_line_items) ? $matched_line_items : NULL;
  }

}
