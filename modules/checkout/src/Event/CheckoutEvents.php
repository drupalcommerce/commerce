<?php

namespace Drupal\commerce_checkout\Event;

/**
 * Defines events for the checkout module.
 */
final class CheckoutEvents {

  /**
   * Name of the event fired when the customer registers at the end of checkout.
   *
   * @Event
   *
   * @see \Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent
   */
  const COMPLETION_REGISTER = 'commerce_checkout.completion_register';

}
