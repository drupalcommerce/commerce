<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\CartProvider.
 */

namespace Drupal\commerce_cart;

use Drupal\commerce_cart\Exception\DuplicateCartException;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Default implementation of the cart provider.
 */
class CartProvider implements CartProviderInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * The loaded cart data, keyed by cart order id, then grouped by uid.
   *
   * Each data item is an array with the following keys:
   * - type: The order type.
   * - store_id: The store id.
   *
   * Example:
   * @code
   * 1 => [
   *   10 => ['type' => 'default', 'store_id' => '1'],
   * ]
   * @endcode
   *
   * @var array
   */
  protected $cartData = [];

  /**
   * Constructs a new CartProvider object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\commerce_cart\CartSessionInterface $cartSession
   *   The cart session.
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $currentUser, CartSessionInterface $cartSession) {
    $this->orderStorage = $entityManager->getStorage('commerce_order');
    $this->currentUser = $currentUser;
    $this->cartSession = $cartSession;
  }

  /**
   * {@inheritdoc}
   */
  public function createCart($orderType, StoreInterface $store, AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $uid = $account->id();
    $storeId = $store->id();
    if ($this->getCartId($orderType, $store, $account)) {
      // Don't allow multiple cart orders matching the same criteria.
      throw new DuplicateCartException("A cart order for type '$orderType', store '$storeId' and account '$uid' already exists.");
    }

    // Create the new cart order.
    $cart = $this->orderStorage->create([
      'type' => $orderType,
      'store_id' => $storeId,
      'uid' => $uid,
      'cart' => TRUE,
    ]);
    $cart->save();
    // Store the new cart order id in the anonymous user's session so that it
    // can be retrieved on the next page load.
    if ($account->isAnonymous()) {
      $this->cartSession->addCartId($cart->id());
    }
    // Cart data has already been loaded, add the new cart order to the list.
    if (isset($this->cartData[$uid])) {
      $this->cartData[$uid][$cart->id()] = [
        'type' => $orderType,
        'store_id' => $storeId,
      ];
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function getCart($orderType, StoreInterface $store, AccountInterface $account = NULL) {
    $cart = NULL;
    $cartId = $this->getCartId($orderType, $store, $account);
    if ($cartId) {
      $cart = $this->orderStorage->load($cartId);
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function getCartId($orderType, StoreInterface $store, AccountInterface $account = NULL) {
    $cartId = NULL;
    $cartData = $this->loadCartData($account);
    if ($cartData) {
      $search = [
        'type' => $orderType,
        'store_id' => $store->id(),
      ];
      $cartId = array_search($search, $cartData);
    }

    return $cartId;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarts(AccountInterface $account = NULL) {
    $carts = [];
    $cartIds = $this->getCartIds($account);
    if ($cartIds) {
      $carts = $this->orderStorage->loadMultiple($cartIds);
    }

    return $carts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCartIds(AccountInterface $account = NULL) {
    $cartData = $this->loadCartData($account);
    return array_keys($cartData);
  }

  /**
   * Loads the cart data for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return array
   *   The cart data.
   */
  protected function loadCartData(AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $uid = $account->id();
    if (isset($this->cartData[$uid])) {
      return $this->cartData[$uid];
    }

    if ($account->isAuthenticated()) {
      $query = $this->orderStorage->getQuery()
        ->condition('cart', TRUE)
        ->condition('uid', $account->id())
        ->sort('order_id', 'DESC');
      $cartIds = $query->execute();
    }
    else {
      $cartIds = $this->cartSession->getCartIds();
    }

    $this->cartData[$uid] = [];
    if (!$cartIds) {
      return [];
    }
    // Getting the cart data and validating the cart ids received from the
    // session requires loading the entities. This is a performance hit, but
    // it's assumed that these entities would be loaded at one point anyway.
    $carts = $this->orderStorage->loadMultiple($cartIds);
    foreach ($carts as $cart) {
      if ($cart->getOwnerId() != $uid || empty($cart->cart)) {
        // Skip orders that are no longer elligible.
        continue;
      }

      $this->cartData[$uid][$cart->id()] = [
        'type' => $cart->getType(),
        'store_id' => $cart->getStoreId(),
      ];
    }

    return $this->cartData[$uid];
  }

}
