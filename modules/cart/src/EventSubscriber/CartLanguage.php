<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderSubscriber.
 */
class CartLanguage implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_cart.entity.add'][] = array('updateLanguage', 0);
    $events['commerce_cart.line_item.update'][] = array('updateLanguage', 0);
    $events['commerce_cart.line_item.remove'][] = array('updateLanguage', 0);
    return $events;
  }

  /**
   * Update the language of the order.
   */
  public function updateLanguage($event) {
    $cart = $event->getCart();

    $order_language = \Drupal::languageManager()->getCurrentLanguage();

    $cart->setLanguage($order_language);

    $event->setCart($cart);
  }

}
