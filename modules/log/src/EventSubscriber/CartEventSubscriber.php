<?php

namespace Drupal\commerce_log\EventSubscriber;

use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructs a new CartEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CART_ENTITY_ADD => ['onCartEntityAdd', -100],
      CartEvents::CART_ORDER_ITEM_REMOVE => ['onCartOrderItemRemove', -100],
    ];
    return $events;
  }

  /**
   * Adds an order log that a purchased entity was added.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The cart event.
   */
  public function onCartEntityAdd(CartEntityAddEvent $event) {
    $cart = $event->getCart();
    $purchased_entity = $event->getOrderItem()->getPurchasedEntity();
    $this->logStorage->generate($cart, 'added_to_cart', [
      'purchased_entity_label' => $purchased_entity->label(),
    ])->save();
  }

  /**
   * Adds an order log that an order item was removed.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemRemoveEvent $event
   *   The cart event.
   */
  public function onCartOrderItemRemove(CartOrderItemRemoveEvent $event) {
    $cart = $event->getCart();
    $order_item = $event->getOrderItem();
    $this->logStorage->generate($cart, 'removed_from_cart', [
      'purchased_entity_label' => $order_item->getPurchasedEntity()->label(),
    ])->save();
  }

}
