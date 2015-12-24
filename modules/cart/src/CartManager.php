<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\CartManager.
 */

namespace Drupal\commerce_cart;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartEmptyEvent;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartLineItemRemoveEvent;
use Drupal\commerce_cart\Event\CartLineItemUpdateEvent;
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
   * The line item storage.
   *
   * @var \Drupal\commerce_order\LineItemStorageInterface
   */
  protected $lineItemStorage;

  /**
   * The line item matcher.
   *
   * @var \Drupal\commerce_cart\LineItemMatcherInterface
   */
  protected $lineItemMatcher;

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
   * @param \Drupal\commerce_cart\LineItemMatcherInterface $line_item_matcher
   *   The line item matcher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LineItemMatcherInterface $line_item_matcher, EventDispatcherInterface $event_dispatcher) {
    $this->lineItemStorage = $entity_type_manager->getStorage('commerce_line_item');
    $this->lineItemMatcher = $line_item_matcher;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function emptyCart(OrderInterface $cart, $save_cart = TRUE) {
    /** @var \Drupal\commerce_order\Entity\LineItemInterface[] $line_items */
    $line_items = $cart->getLineItems();
    foreach ($line_items as $line_item) {
      $line_item->delete();
    }
    $cart->setLineItems([]);

    $this->eventDispatcher->dispatch(CartEvents::CART_EMPTY, new CartEmptyEvent($cart, $line_items));
    if ($save_cart) {
      $cart->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntity(OrderInterface $cart, PurchasableEntityInterface $entity, $quantity = 1, $combine = TRUE, $save_cart = TRUE) {
    $line_item = $this->createLineItem($entity, $quantity);
    return $this->addLineItem($cart, $line_item, $combine);
  }

  /**
   * {@inheritdoc}
   */
  public function createLineItem(PurchasableEntityInterface $entity, $quantity = 1) {
    $line_item = $this->lineItemStorage->createFromPurchasableEntity($entity, [
      'quantity' => $quantity,
      // @todo Remove once the price calculation is in place.
      'unit_price' => $entity->price,
    ]);

    return $line_item;
  }

  /**
   * {@inheritdoc}
   */
  public function addLineItem(OrderInterface $cart, LineItemInterface $line_item, $combine = TRUE, $save_cart = TRUE) {
    $purchased_entity = $line_item->getPurchasedEntity();
    $quantity = $line_item->getQuantity();
    $matching_line_item = NULL;
    if ($combine) {
      $matching_line_item = $this->lineItemMatcher->match($line_item, $cart->getLineItems());
    }
    $needs_cart_save = FALSE;
    if ($matching_line_item) {
      $new_quantity = $matching_line_item->getQuantity() + $quantity;
      $matching_line_item->setQuantity($new_quantity);
      $matching_line_item->save();
    }
    else {
      $line_item->save();
      $cart->addLineItem($line_item);
      $needs_cart_save = TRUE;
    }

    $event = new CartEntityAddEvent($cart, $purchased_entity, $quantity, $line_item);
    $this->eventDispatcher->dispatch(CartEvents::CART_ENTITY_ADD, $event);
    if ($needs_cart_save && $save_cart) {
      $cart->save();
    }

    return $line_item;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLineItem(OrderInterface $cart, LineItemInterface $line_item) {
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $original_line_item */
    $original_line_item = $this->lineItemStorage->loadUnchanged($line_item->id());
    $line_item->save();
    $event = new CartLineItemUpdateEvent($cart, $line_item, $original_line_item);
    $this->eventDispatcher->dispatch(CartEvents::CART_LINE_ITEM_UPDATE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function removeLineItem(OrderInterface $cart, LineItemInterface $line_item, $save_cart = TRUE) {
    $line_item->delete();
    $cart->removeLineItem($line_item);
    $this->eventDispatcher->dispatch(CartEvents::CART_LINE_ITEM_REMOVE, new CartLineItemRemoveEvent($cart, $line_item));
    if ($save_cart) {
      $cart->save();
    }
  }

}
