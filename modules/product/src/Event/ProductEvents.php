<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Event\ProductEvents.
 */

namespace Drupal\commerce_product\Event;

final class ProductEvents {

  /**
   * Name of the event fired after loading a product.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_LOAD = 'commerce_product.commerce_product.load';

  /**
   * Name of the event fired after creating a new product.
   *
   * Fired before the product is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_CREATE = 'commerce_product.commerce_product.create';

  /**
   * Name of the event fired before saving a product.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_PRESAVE = 'commerce_product.commerce_product.presave';

  /**
   * Name of the event fired after saving a new product.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_INSERT = 'commerce_product.commerce_product.insert';

  /**
   * Name of the event fired after saving an existing product.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_UPDATE = 'commerce_product.commerce_product.update';

  /**
   * Name of the event fired before deleting a product.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_PREDELETE = 'commerce_product.commerce_product.predelete';

  /**
   * Name of the event fired after deleting a product.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_DELETE = 'commerce_product.commerce_product.delete';

  /**
   * Name of the event fired after saving a new product translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_TRANSLATION_INSERT = 'commerce_product.commerce_product.translation_insert';

  /**
   * Name of the event fired after deleting a product translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductEvent
   */
  const PRODUCT_TRANSLATION_DELETE = 'commerce_product.commerce_product.translation_delete';

  /**
   * Name of the event fired after loading a product variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_LOAD = 'commerce_product.commerce_product_variation.load';

  /**
   * Name of the event fired after creating a new product variation.
   *
   * Fired before the product variation is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_CREATE = 'commerce_product.commerce_product_variation.create';

  /**
   * Name of the event fired before saving a product variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_PRESAVE = 'commerce_product.commerce_product_variation.presave';

  /**
   * Name of the event fired after saving a new product variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_INSERT = 'commerce_product.commerce_product_variation.insert';

  /**
   * Name of the event fired after saving an existing product variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_UPDATE = 'commerce_product.commerce_product_variation.update';

  /**
   * Name of the event fired before deleting a product variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_PREDELETE = 'commerce_product.commerce_product_variation.predelete';

  /**
   * Name of the event fired after deleting a product variation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_DELETE = 'commerce_product.commerce_product_variation.delete';

  /**
   * Name of the event fired after saving a new product variation translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_TRANSLATION_INSERT = 'commerce_product.commerce_product_variation.translation_insert';

  /**
   * Name of the event fired after deleting a product variation translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\ProductVariationEvent
   */
  const PRODUCT_VARIATION_TRANSLATION_DELETE = 'commerce_product.commerce_product_variation.translation_delete';

  /**
   * Name of the event fired when filtering variations.
   *
   * @Event
   *
   * @see \Drupal\commerce_product\Event\FilterVariationsEvent
   */
  const FILTER_VARIATIONS = "commerce_product.filter_variations";

}
