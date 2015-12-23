<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Event\CartEvents.
 */

namespace Drupal\commerce_cart\Event;

/**
 * Defines events for the cart module.
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

  /**
   * Name of the event fired when altering the list of comparison fields.
   *
   * Use this event to add additional field names to the list of fields used
   * to determine whether a line item can be combined into an existing line
   * item.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\LineItemComparisonFieldsEvent
   */
  const LINE_ITEM_COMPARISON_FIELDS = 'commerce_cart.line_item.comparison_fields';

}
