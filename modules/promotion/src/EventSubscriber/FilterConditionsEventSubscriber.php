<?php

namespace Drupal\commerce_promotion\EventSubscriber;

use Drupal\commerce\Event\FilterConditionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterConditionsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce.filter_conditions' => 'onFilterConditions',
    ];
    return $events;
  }

  /**
   * Removes unneeded conditions.
   *
   * Promotions have store and order_types base fields that are used for
   * filtering, so there's no need to have conditions targeting the same data.
   *
   * @param \Drupal\commerce\Event\FilterConditionsEvent $event
   *   The event.
   */
  public function onFilterConditions(FilterConditionsEvent $event) {
    if ($event->getParentEntityTypeId() == 'commerce_promotion') {
      $definitions = $event->getDefinitions();
      unset($definitions['order_store']);
      unset($definitions['order_type']);
      $event->setDefinitions($definitions);
    }
  }

}
