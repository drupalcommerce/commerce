<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Event\OrderEvents.
 */

namespace Drupal\commerce_order\Event;

final class OrderEvents {

  /**
   * Name of the event fired after loading an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_LOAD = 'commerce_order.commerce_order.load';

  /**
   * Name of the event fired after creating a new order.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_CREATE = 'commerce_order.commerce_order.create';

  /**
   * Name of the event fired before saving an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_PRESAVE = 'commerce_order.commerce_order.presave';

  /**
   * Name of the event fired after saving a new order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_INSERT = 'commerce_order.commerce_order.insert';

  /**
   * Name of the event fired after saving an existing order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_UPDATE = 'commerce_order.commerce_order.update';

  /**
   * Name of the event fired before deleting an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_PREDELETE = 'commerce_order.commerce_order.predelete';

  /**
   * Name of the event fired after deleting an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_DELETE = 'commerce_order.commerce_order.delete';

  /**
   * Name of the event fired after loading a line item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_LOAD = 'commerce_order.commerce_line_item.load';

  /**
   * Name of the event fired after creating a new line item.
   *
   * Fired before the line item is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_CREATE = 'commerce_order.commerce_line_item.create';

  /**
   * Name of the event fired before saving a line item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_PRESAVE = 'commerce_order.commerce_line_item.presave';

  /**
   * Name of the event fired after saving a new line item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_INSERT = 'commerce_order.commerce_line_item.insert';

  /**
   * Name of the event fired after saving an existing line item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_UPDATE = 'commerce_order.commerce_line_item.update';

  /**
   * Name of the event fired before deleting a line item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_PREDELETE = 'commerce_order.commerce_line_item.predelete';

  /**
   * Name of the event fired after deleting a line item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\LineItemEvent
   */
  const LINE_ITEM_DELETE = 'commerce_order.commerce_line_item.delete';

}
