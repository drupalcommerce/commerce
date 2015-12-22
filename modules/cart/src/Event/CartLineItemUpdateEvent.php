<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Event\CartLineItemUpdateEvent.
 */

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\LineItemInterface;
use SebastianBergmann\Diff\Line;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the cart line item update event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class CartLineItemUpdateEvent extends Event {

  /**
   * The cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The updated line item.
   *
   * @var \Drupal\commerce_order\Entity\LineItemInterface
   */
  protected $lineItem;

  /**
   * The original line item.
   *
   * @var \Drupal\commerce_order\Entity\LineItemInterface
   */
  protected $originalLineItem;

  /**
   * Constructs a new CartLineItemUpdateEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The updated line item.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $original_line_item
   *   The original line item.
   */
  public function __construct(OrderInterface $cart, LineItemInterface $line_item, LineItemInterface $original_line_item) {
    $this->cart = $cart;
    $this->lineItem = $line_item;
    $this->originalLineItem = $original_line_item;
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
   * Gets the updated line item.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The updated line item.
   */
  public function getLineItem() {
    return $this->lineItem;
  }

  /**
   * Gets the original line item.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The original line item.
   */
  public function getOriginalLineItem() {
    return $this->originalLineItem;
  }

}
