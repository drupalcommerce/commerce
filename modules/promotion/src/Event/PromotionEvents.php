<?php

namespace Drupal\commerce_promotion\Event;

final class PromotionEvents {

  /**
   * Name of the event fired after loading a promotion.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_LOAD = 'commerce_promotion.commerce_promotion.load';

  /**
   * Name of the event fired after creating a new promotion.
   *
   * Fired before the promotion is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_CREATE = 'commerce_promotion.commerce_promotion.create';

  /**
   * Name of the event fired before saving a promotion.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_PRESAVE = 'commerce_promotion.commerce_promotion.presave';

  /**
   * Name of the event fired after saving a new promotion.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_INSERT = 'commerce_promotion.commerce_promotion.insert';

  /**
   * Name of the event fired after saving an existing promotion.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_UPDATE = 'commerce_promotion.commerce_promotion.update';

  /**
   * Name of the event fired before deleting a promotion.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_PREDELETE = 'commerce_promotion.commerce_promotion.predelete';

  /**
   * Name of the event fired after deleting a promotion.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_DELETE = 'commerce_promotion.commerce_promotion.delete';

  /**
   * Name of the event fired after saving a new promotion translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_TRANSLATION_INSERT = 'commerce_promotion.commerce_promotion.translation_insert';

  /**
   * Name of the event fired after deleting a promotion translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_promotion\Event\PromotionEvent
   */
  const PROMOTION_TRANSLATION_DELETE = 'commerce_promotion.commerce_promotion.translation_delete';

}
