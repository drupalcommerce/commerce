<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Drupal\Core\Language\LanguageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrderSubscriber.
 */
class CartLanguage implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * CartLanguage constructor.
   *
   * @param Drupal\Core\Language\LanguageManager $language_manager
   */
  public function __construct(LanguageManager $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_cart.entity.add'][] = ['updateLanguage', 0];
    $events['commerce_cart.line_item.update'][] = ['updateLanguage', 0];
    $events['commerce_cart.line_item.remove'][] = ['updateLanguage', 0];
    return $events;
  }

  /**
   * Update the language of the order.
   */
  public function updateLanguage($event) {
    $cart = $event->getCart();

    $order_language = $this->languageManager->getCurrentLanguage();

    $cart->setLanguage($order_language);

    $event->setCart($cart);
  }

}
