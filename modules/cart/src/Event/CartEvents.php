<?php

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
   * Name of the event fired after updating a cart's order item.
   *
   * Fired before the cart order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\CartOrderItemUpdateEvent
   */
  const CART_ORDER_ITEM_UPDATE = 'commerce_cart.order_item.update';

  /**
   * Name of the event fired after removing an order item from the cart.
   *
   * Fired before the cart order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\CartOrderItemRemoveEvent
   */
  const CART_ORDER_ITEM_REMOVE = 'commerce_cart.order_item.remove';

  /**
   * Name of the event fired when altering the list of comparison fields.
   *
   * Use this event to add additional field names to the list of fields used
   * to determine whether an order item can be combined into an existing order
   * item.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent
   */
  const ORDER_ITEM_COMPARISON_FIELDS = 'commerce_cart.order_item.comparison_fields';

}
