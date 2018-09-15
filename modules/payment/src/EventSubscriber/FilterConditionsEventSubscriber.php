<?php

namespace Drupal\commerce_payment\EventSubscriber;

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
   * Removes the payment gateway condition on payment gateways.
   *
   * @param \Drupal\commerce\Event\FilterConditionsEvent $event
   *   The event.
   */
  public function onFilterConditions(FilterConditionsEvent $event) {
    if ($event->getParentEntityTypeId() == 'commerce_payment_gateway') {
      $definitions = $event->getDefinitions();
      unset($definitions['order_payment_gateway']);
      $event->setDefinitions($definitions);
    }
  }

}
