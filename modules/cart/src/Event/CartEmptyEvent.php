<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Event\CartEmptyEvent.
 */

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart empty event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartEmptyEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The removed line items.
   *
   * @var \Drupal\commerce_order\Entity\LineItemInterface[]
   */
  protected $lineItems;

  /**
   * Constructs a new CartEmptyEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\LineItemInterface[] $line_items
   *   The removed line items.
   */
  public function __construct(OrderInterface $cart, array $line_items) {
    $this->cart = $cart;
    $this->lineItems = $line_items;
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
   * Gets the removed line items.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface[]
   *   The removed line items.
   */
  public function getLineItems() {
    return $this->lineItems;
  }

}
