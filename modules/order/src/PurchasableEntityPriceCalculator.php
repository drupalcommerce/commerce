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

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The order processors.
   *
   * @var \Drupal\commerce_order\OrderProcessorInterface[]
   */
  protected $processors = [];

  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentStoreInterface $current_store, AccountProxyInterface $current_user, OrderTypeResolverInterface $order_type_resolver, ChainPriceResolverInterface $chain_price_resolver, AdjustmentTypeManager $adjustment_type_manager) {
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->orderTypeResolver = $order_type_resolver;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->adjustmentTypeManager = $adjustment_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(OrderProcessorInterface $processor) {
    $this->processors[] = $processor;
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
      return [
        'original' => $purchasable_entity->getPrice(),
        'resolved' => $resolved_price,
        'adjustments' => [],
        'calculated' => $resolved_price,
      ];
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
    $order_item->order_id = $order;

    foreach ($this->processors as $processor) {
      if ($processor instanceof AvailabilityOrderProcessor) {
        // @todo this is an issue we need to work around.
        // We need a method on order processors to deem if they provide
        // adjustments. However, that would break BC. Maybe we just need a new
        // service tag?
        continue;
      }
      $processor->process($order);
    }

    $calculated_price = $order_item->getUnitPrice();
    $types = $this->adjustmentTypeManager->getDefinitions();
    $adjustments = [];
    foreach ($order_item->getAdjustments() as $adjustment) {
      if (!in_array($adjustment->getType(), $adjustment_types)) {
        continue;
      }
      if ($adjustment->isIncluded()) {
        continue;
      }

      $calculated_price = $calculated_price->add($adjustment->getAmount());

      $type = $adjustment->getType();
      $source_id = $adjustment->getSourceId();
      if (empty($source_id)) {
        // Adjustments without a source ID are always shown standalone.
        $key = count($adjustments);
      }
      else {
        // Adjustments with the same
        // type and source ID are combined.
        $key = $type . '_' . $source_id;
      }

      if (empty($adjustments[$key])) {
        $adjustments[$key] = [
          'type' => $type,
          'label' => $adjustment->getLabel(),
          'total' => $adjustment->getAmount(),
          'weight' => $types[$type]['weight'],
        ];
      }
      else {
        $adjustments[$key]['total'] = $adjustments[$key]['total']->add($adjustment->getAmount());
      }
    }
    return [
      'original' => $purchasable_entity->getPrice(),
      'resolved' => $resolved_price,
      'adjustments' => $adjustments,
      'calculated' => $calculated_price,
    ];
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
