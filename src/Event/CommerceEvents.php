<?php

namespace Drupal\commerce\Event;

/**
 * Defines events for the base Commerce module.
 *
 * Note that submodules have their own defined events.
 */
final class CommerceEvents {

  /**
   * Name of the event fired when filtering available conditions.
   *
   * @Event
   *
   * @see \Drupal\commerce\Event\FilterConditionsEvent
   */
  const FILTER_CONDITIONS = 'commerce.filter_conditions';

  /**
   * Name of the event fired when altering the referenceable plugin types.
   *
   * @Event
   *
   * @see \Drupal\commerce\Event\ReferenceablePluginTypesEvent.php
   */
  const REFERENCEABLE_PLUGIN_TYPES = 'commerce.referenceable_plugin_types';

  /**
   * Name of the event fired after sending an email via the mail handler.
   *
   * @Event
   *
   * @see \Drupal\commerce\Event\PostMailSendEvent.php
   */
  const POST_MAIL_SEND = 'commerce.post_mail_send';

}
