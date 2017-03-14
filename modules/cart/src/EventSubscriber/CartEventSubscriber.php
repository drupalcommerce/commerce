<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Link;

class CartEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new OrderEventSubscriber object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(CartProviderInterface $cart_provider, TranslationInterface $string_translation) {
    $this->cartProvider = $cart_provider;
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
