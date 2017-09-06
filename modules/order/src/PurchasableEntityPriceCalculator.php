<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

class PurchasableEntityPriceCalculator implements PurchasableEntityPriceCalculatorInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The order refresh.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;


  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentStoreInterface $current_store, AccountProxyInterface $current_user, OrderTypeResolverInterface $order_type_resolver, OrderRefreshInterface $order_refresh, ChainPriceResolverInterface $chain_price_resolver) {
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->orderTypeResolver = $order_type_resolver;
    $this->orderRefresh = $order_refresh;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public function calculate(PurchasableEntityInterface $purchasable_entity, $quantity, array $adjustment_types = []) {
    $store = $this->selectStore($purchasable_entity);

    $context = new Context($this->currentUser, $store);
    $resolved_price = $this->chainPriceResolver->resolve($purchasable_entity, $quantity, $context);

    // We do not need adjustments to be calculated, return the resolved price.
    if (empty($adjustment_types)) {
      return $resolved_price;
    }

    $order_item = $this->orderItemStorage->createFromPurchasableEntity($purchasable_entity);
    $order_item->setUnitPrice($resolved_price);
    $order_type_id = $this->orderTypeResolver->resolve($order_item);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->orderStorage->create([
      'type' => $order_type_id,
      'store_id' => $store->id(),
      'uid' => $this->currentUser->id(),
    ]);
    $order->addItem($order_item);

    $this->orderRefresh->refresh($order);

    $calculated_price = $order_item->getUnitPrice();
    foreach ($order_item->getAdjustments() as $adjustment) {
      if (!in_array($adjustment->getType(), $adjustment_types)) {
        continue;
      }
      $calculated_price = $calculated_price->add($adjustment->getAmount());
    }
    return $calculated_price;
  }

  /**
   * Selects the store for the given purchasable entity.
   *
   * If the entity is sold from one store, then that store is selected.
   * If the entity is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The entity being added to cart.
   *
   * @throws \Exception
   *   When the entity can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   */
  protected function selectStore(PurchasableEntityInterface $entity) {
    $stores = $entity->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
    }
    elseif (count($stores) === 0) {
      // Malformed entity.
      throw new \Exception('The given entity is not assigned to any store.');
    }
    else {
      $store = $this->currentStore->getStore();
      if (!in_array($store, $stores)) {
        // Indicates that the site listings are not filtered properly.
        throw new \Exception("The given entity can't be calculated for the current store.");
      }
    }

    return $store;
  }

}
