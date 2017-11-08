<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Link;

class CartEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new CartEventSubscriber object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CART_ENTITY_ADD => 'displayAddToCartMessage',
    ];
    return $events;
  }

  /**
   * Displays an add to cart message.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The add to cart event.
   */
  public function displayAddToCartMessage(CartEntityAddEvent $event) {
    $purchased_entity = $event->getEntity();
    drupal_set_message($this->t('@entity added to @cart-link.', [
      '@entity' => $purchased_entity->label(),
      '@cart-link' => Link::createFromRoute($this->t('your cart', [], ['context' => 'cart link']), 'commerce_cart.page')->toString(),
    ]));
  }

}
