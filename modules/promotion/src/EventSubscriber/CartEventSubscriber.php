<?php

namespace Drupal\commerce_promotion\EventSubscriber;

use Drupal\commerce_cart\Event\CartEmptyEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CART_EMPTY => ['onCartEmpty'],
    ];
    return $events;
  }

  /**
   * Removes coupons when the cart has been emptied.
   *
   * @param \Drupal\commerce_cart\Event\CartEmptyEvent $event
   *   The cart event.
   */
  public function onCartEmpty(CartEmptyEvent $event) {
    $event
      ->getCart()
      ->set('coupons', []);
  }

}
