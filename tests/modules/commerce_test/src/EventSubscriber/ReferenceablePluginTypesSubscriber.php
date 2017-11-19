<?php

namespace Drupal\commerce_test\EventSubscriber;

use Drupal\commerce\Event\CommerceEvents;
use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReferenceablePluginTypesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CommerceEvents::REFERENCEABLE_PLUGIN_TYPES][] = ['onPluginTypes'];
    return $events;
  }

  /**
   * Registers the 'commerce_payment_method_type' plugin type as referenceable.
   *
   * Needed by PluginSelectTest.
   *
   * @param \Drupal\commerce\Event\ReferenceablePluginTypesEvent $event
   *   The event.
   */
  public function onPluginTypes(ReferenceablePluginTypesEvent $event) {
    $types = $event->getPluginTypes();
    $types['commerce_payment_method_type'] = $this->t('Payment method type');
    $event->setPluginTypes($types);
  }

}
