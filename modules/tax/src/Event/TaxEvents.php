<?php

namespace Drupal\commerce_tax\Event;

final class TaxEvents {

  /**
   * Name of the event fired when determining the customer's profile.
   *
   * By default the billing profile is used to determine tax type
   * applicability for each order item. Modules should use this event
   * to select a shipping profile instead, when available.
   *
   * @Event
   *
   * @see \Drupal\commerce_tax\Event\CustomerProfileEvent
   */
  const CUSTOMER_PROFILE = 'commerce_tax.customer_profile';

}
