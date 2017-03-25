<?php

namespace Drupal\commerce_cart;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartEmptyEvent;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\commerce_cart\Event\CartOrderItemUpdateEvent;
use Drupal\commerce_price\Calculator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default implementation of the cart manager.
 *
 * Fires its own events, different from the order entity events by being a
 * result of user interaction (add to cart form, cart view, etc).
 */
class CartManager implements CartManagerInterface {

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The order item matcher.
   *
   * @var \Drupal\commerce_cart\OrderItemMatcherInterface
   */
  protected $orderItemMatcher;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CartManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_cart\OrderItemMatcherInterface $order_item_matcher
   *   The order item matcher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OrderItemMatcherInterface $order_item_matcher, EventDispatcherInterface $event_dispatcher) {
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
    $this->orderItemMatcher = $order_item_matcher;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function emptyCart(OrderInterface $cart, $save_cart = TRUE) {
    $order_items = $cart->getItems();
    foreach ($order_items as $order_item) {
      $order_item->delete();
    }
    $cart->setItems([]);
    $cart->setAdjustments([]);

    $this->eventDispatcher->dispatch(CartEvents::CART_EMPTY, new CartEmptyEvent($cart, $order_items));
    $this->resetCheckoutStep($cart);
    if ($save_cart) {
      $cart->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntity(OrderInterface $cart, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_cart = TRUE) {
    $order_item = $this->createOrderItem($entity, $quantity);
    return $this->addOrderItem($cart, $order_item, $combine, $save_cart);
  }

  /**
   * {@inheritdoc}
   */
  public function createOrderItem(PurchasableEntityInterface $entity, $quantity = 1) {
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($entity, [
      'quantity' => $quantity,
    ]);

    return $order_item;
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderItem(OrderInterface $cart, OrderItemInterface $order_item, $combine = TRUE, $save_cart = TRUE) {
    $purchased_entity = $order_item->getPurchasedEntity();
    $quantity = $order_item->getQuantity();
    $matching_order_item = NULL;
    if ($combine) {
      $matching_order_item = $this->orderItemMatcher->match($order_item, $cart->getItems());
    }
    if ($matching_order_item) {
      $new_quantity = Calculator::add($matching_order_item->getQuantity(), $quantity);
      $matching_order_item->setQuantity($new_quantity);
      $matching_order_item->save();
      $saved_order_item = $matching_order_item;
    }
    else {
      $order_item->save();
      $cart->addItem($order_item);
      $saved_order_item = $order_item;
    }

    if ($purchased_entity) {
      $event = new CartEntityAddEvent($cart, $purchased_entity, $quantity, $saved_order_item);
      $this->eventDispatcher->dispatch(CartEvents::CART_ENTITY_ADD, $event);
    }

    $this->resetCheckoutStep($cart);
    if ($save_cart) {
      $cart->save();
    }

    return $saved_order_item;
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrderItem(OrderInterface $cart, OrderItemInterface $order_item, $save_cart = TRUE) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $original_order_item */
    $original_order_item = $this->orderItemStorage->loadUnchanged($order_item->id());
    $order_item->save();
    $event = new CartOrderItemUpdateEvent($cart, $order_item, $original_order_item);
    $this->eventDispatcher->dispatch(CartEvents::CART_ORDER_ITEM_UPDATE, $event);
    $this->resetCheckoutStep($cart);
    if ($save_cart) {
      $cart->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeOrderItem(OrderInterface $cart, OrderItemInterface $order_item, $save_cart = TRUE) {
    $order_item->delete();
    $cart->removeItem($order_item);
    $this->eventDispatcher->dispatch(CartEvents::CART_ORDER_ITEM_REMOVE, new CartOrderItemRemoveEvent($cart, $order_item));
    $this->resetCheckoutStep($cart);
    if ($save_cart) {
      $cart->save();
    }
  }

  /**
   * Resets the checkout step.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   */
  protected function resetCheckoutStep(OrderInterface $cart) {
    if ($cart->hasField('checkout_step')) {
      $cart->set('checkout_step', '');
    }
  }

}
