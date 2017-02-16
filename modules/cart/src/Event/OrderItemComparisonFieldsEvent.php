<?php

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order item comparison fields event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class OrderItemComparisonFieldsEvent extends Event {

  /**
   * The comparison fields.
   *
   * @var string[]
   */
  protected $comparisonFields;

  /**
   * The order item being added to the cart.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * Constructs a new OrderItemComparisonFieldsEvent.
   *
   * @param string[] $comparison_fields
   *   The comparison fields.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item being added to the cart.
   */
  public function __construct(array $comparison_fields, OrderItemInterface $order_item) {
    $this->comparisonFields = $comparison_fields;
    $this->orderItem = $order_item;
  }

  /**
   * Gets the comparison fields.
   *
   * @return string[]
   *   The comparison fields.
   */
  public function getComparisonFields() {
    return $this->comparisonFields;
  }

  /**
   * Sets the comparison fields.
   *
   * @param string[] $comparison_fields
   *   The comparison fields.
   */
  public function setComparisonFields(array $comparison_fields) {
    $this->comparisonFields = $comparison_fields;
  }

  /**
   * The order item being added to the cart.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item being added to the cart.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

}
