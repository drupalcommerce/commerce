<?php

namespace Drupal\commerce_cart\EventSubscriber;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * Constructs a new QueryAccessSubscriber object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(CartProviderInterface $cart_provider, CartSessionInterface $cart_session) {
    $this->cartProvider = $cart_provider;
    $this->cartSession = $cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.commerce_order' => 'onQueryAccess',
    ];
  }

  /**
   * Modifies the access conditions for cart orders.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The event.
   */
  public function onQueryAccess(QueryAccessEvent $event) {
    if ($event->getOperation() != 'view') {
      return;
    }

    $conditions = $event->getConditions();
    // The user already has full access due to a "administer commerce_order"
    // or "view commerce_order" permission.
    if (!$conditions->count() && !$conditions->isAlwaysFalse()) {
      return;
    }

    $account = $event->getAccount();
    // Any user can view their own active carts, regardless of any permissions.
    // Anonymous users can also see their own completed carts.
    $cart_ids = $this->cartProvider->getCartIds($account);
    if ($account->isAnonymous()) {
      $completed_cart_ids = $this->cartSession->getCartIds(CartSessionInterface::COMPLETED);
      $cart_ids = array_merge($cart_ids, $completed_cart_ids);
    }

    if (!empty($cart_ids)) {
      $conditions->addCondition('order_id', $cart_ids);
      $conditions->alwaysFalse(FALSE);
    }
  }

}
