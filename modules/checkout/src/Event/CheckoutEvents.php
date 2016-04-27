<?php

namespace Drupal\commerce_checkout\Event;

/**
 * Defines events for the checkout module.
 */
final class CheckoutEvents {

  /**
   * Name of the event fired when an order completes checkout.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_checkout\Event\CheckoutCompleteEvent
   */
  const CHECKOUT_COMPLETE = 'commerce_checkout.checkout.complete';

}
