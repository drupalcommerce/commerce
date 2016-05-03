<?php

namespace Drupal\commerce_cart;

use Drupal\commerce_cart\Event\CartAssignEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CartAssignment implements CartAssignmentInterface {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CartAssignment object.
   *
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(CartSessionInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->cartSession = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function assignAll(UserInterface $account) {
    $cart_ids = $this->cartSession->getCartIds();
    if ($cart_ids) {
      $cart_storage = $this->entityTypeManager->getStorage('commerce_order');
      /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
      $carts = $cart_storage->loadMultiple($cart_ids);
      foreach ($carts as $cart) {
        $this->assign($cart, $account);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assign(OrderInterface $cart, UserInterface $account) {
    if (!empty($cart->getOwnerId())) {
      // Skip cart orders which already have an owner.
      return;
    }

    $cart->setOwner($account);
    $cart->setEmail($account->getEmail());
    // Update the referenced billing profile.
    $billing_profile = $cart->getBillingProfile();
    if ($billing_profile && empty($billing_profile->getOwnerId())) {
      $billing_profile->setOwner($account);
      $billing_profile->save();
    }
    // Notify other modules.
    $event = new CartAssignEvent($cart, $account);
    $this->eventDispatcher->dispatch(CartEvents::CART_ASSIGN, $event);

    $cart->save();
  }

}
