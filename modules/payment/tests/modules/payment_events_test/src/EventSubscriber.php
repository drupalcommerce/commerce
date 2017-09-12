<?php

namespace Drupal\payment_events_test;

use Drupal\commerce_payment\Event\PaymentEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface {

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the Event Subscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Reacts to payment event.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   * @param string $name
   *   The name of the event.
   */
  public function paymentEvent(PaymentEvent $event, $name) {
    $this->state->set('payment_events_test.event', [
      'event_name' => $name,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PaymentEvents::PAYMENT_LOAD][] = ['paymentEvent'];
    $events[PaymentEvents::PAYMENT_CREATE][] = ['paymentEvent'];
    return $events;
  }

}
