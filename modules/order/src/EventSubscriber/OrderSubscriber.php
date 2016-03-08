<?php

namespace Drupal\commerce_order\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderSubscriber.
 */
class OrderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.commerce_order.presave'][] = array('updateLanguage', 0);
    $events['commerce_order.commerce_order.update'][] = array('updateLanguage', 0);
    return $events;
  }

  /**
   * Update the language of the order.
   */
  public function updateLanguage($event) {
    $order = $event->getOrder();

    $order_language = \Drupal::languageManager()->getCurrentLanguage();

    $order->setLanguage($order_language);

    $event->setOrder($order);
  }

}