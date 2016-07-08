<?php

namespace Drupal\commerce_discount\Event;

final class DiscountEvents {

  /**
   * Name of the event fired after loading a discount.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_LOAD = 'commerce_discount.commerce_discount.load';

  /**
   * Name of the event fired after creating a new discount.
   *
   * Fired before the discount is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_CREATE = 'commerce_discount.commerce_discount.create';

  /**
   * Name of the event fired before saving a discount.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_PRESAVE = 'commerce_discount.commerce_discount.presave';

  /**
   * Name of the event fired after saving a new discount.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_INSERT = 'commerce_discount.commerce_discount.insert';

  /**
   * Name of the event fired after saving an existing discount.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_UPDATE = 'commerce_discount.commerce_discount.update';

  /**
   * Name of the event fired before deleting a discount.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_PREDELETE = 'commerce_discount.commerce_discount.predelete';

  /**
   * Name of the event fired after deleting a discount.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_DELETE = 'commerce_discount.commerce_discount.delete';

  /**
   * Name of the event fired after saving a new discount translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_TRANSLATION_INSERT = 'commerce_discount.commerce_discount.translation_insert';

  /**
   * Name of the event fired after deleting a discount translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_discount\Event\DiscountEvent
   */
  const DISCOUNT_TRANSLATION_DELETE = 'commerce_discount.commerce_discount.translation_delete';

}
