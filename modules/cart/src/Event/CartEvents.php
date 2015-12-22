<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Event\CartEvents.
 */

namespace Drupal\commerce_cart\Event;

/**
 * Defines events for the cart module.
 *
 * These events are fired by CartManager as a result of user interaction
 * (add to cart form, cart view, etc).
 */
final class CartEvents {

  /**
   * Name of the event fired after emptying the cart order.
   *
   * Fired before the cart order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\CartEmptyEvent
   */
  const CART_EMPTY = 'commerce_cart.cart.empty';

  /**
   * Name of the event fired after adding a purchasable entity to the cart.
   *
   * Fired before the cart order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\CartEntityAddEvent
   */
  const CART_ENTITY_ADD = 'commerce_cart.entity.add';

  /**
   * Name of the event fired after updating a cart's line item.
   *
   * Fired before the cart order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\CartLineItemUpdateEvent
   */
  const CART_LINE_ITEM_UPDATE = 'commerce_cart.line_item.update';

  /**
   * Name of the event fired after removing a line item from the cart.
   *
   * Fired before the cart order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\CartLineItemRemoveEvent
   */
  const CART_LINE_ITEM_REMOVE = 'commerce_cart.line_item.remove';

}
