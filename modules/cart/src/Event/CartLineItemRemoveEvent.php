<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Event\CartLineItemRemoveEvent.
 */

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\LineItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart line item remove event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartLineItemRemoveEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The removed line item.
   *
   * @var \Drupal\commerce_order\Entity\LineItemInterface
   */
  protected $lineItem;

  /**
   * Constructs a new CartLineItemRemoveEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The removed line item.
   */
  public function __construct(OrderInterface $cart, LineItemInterface $line_item) {
    $this->cart = $cart;
    $this->lineItem = $line_item;
  }

  /**
   * Gets the cart order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The cart order.
   */
  public function getCart() {
    return $this->cart;
  }

  /**
   * Gets the removed line item.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The line item entity.
   */
  public function getLineItem() {
    return $this->lineItem;
  }

}
