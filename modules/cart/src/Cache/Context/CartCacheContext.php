<?php

namespace Drupal\commerce_cart\Cache\Context;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the CartCacheContext service, for "per cart" caching.
 *
 * Cache context ID: 'cart'.
 */
class CartCacheContext implements CacheContextInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The cart provider service.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new CartCacheContext object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider service.
   */
  public function __construct(AccountInterface $account, CartProviderInterface $cart_provider) {
    $this->account = $account;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Current cart IDs');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return implode(':', $this->cartProvider->getCartIds($this->account));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $metadata = new CacheableMetadata();
    foreach ($this->cartProvider->getCarts($this->account) as $cart) {
      $metadata->addCacheableDependency($cart);
    }
    return $metadata;
  }

}
