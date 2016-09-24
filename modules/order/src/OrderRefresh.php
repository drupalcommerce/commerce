<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Resolver\ChainPriceResolver;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Default implementation for order refresh.
 */
class OrderRefresh implements OrderRefreshInterface {

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The order refresh processors.
   *
   * @var \Drupal\commerce_order\OrderProcessorInterface[]
   */
  protected $processors;

  protected static $refreshingOrders = [];

  /**
   * Constructs a new OrderRefresh object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ChainPriceResolverInterface $chain_price_resolver, AccountProxyInterface $current_user) {
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->chainPriceResolver = $chain_price_resolver;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(OrderProcessorInterface $processor, $priority) {
    $this->processors[$priority] = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function needsRefresh(OrderInterface $order) {
    // Refresh should only run on draft orders.
    if ($order->getState()->value != 'draft') {
      return FALSE;
    }

    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());
    // Check the order's changed time against the refresh frequency.
    // We use time() since the REQUEST_TIME constant can become stale. This is
    // especially true during CLI operations.
    if (time() - $order->getChangedTime() < $order_type->getRefreshFrequency()) {
      return FALSE;
    }

    $refresh_owner_only = ($order_type->getRefreshMode() == OrderType::REFRESH_OWNER);
    if ($refresh_owner_only  && ($order->getOwnerId() != $this->currentUser->id())) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(OrderInterface $order) {
    // Do not attempt to refresh an order that is already refreshing.
    if (!isset(self::$refreshingOrders[$order->id()])) {
      self::$refreshingOrders[$order->id()] = TRUE;

      // Do not remove adjustments added in the user interface.
      $adjustments = $order->getAdjustments();
      foreach ($adjustments as $key => $adjustment) {
        if ($adjustment->getType() != 'custom') {
          unset($adjustments[$key]);
        }
      }
      $order->setAdjustments($adjustments);

      foreach ($order->getItems() as $order_item) {
        $order_item->setAdjustments([]);

        $purchased_entity = $order_item->getPurchasedEntity();
        // @todo resolve pricing for items without purchaseable entity.
        if ($purchased_entity) {
          $order_item->setTitle($purchased_entity->getOrderItemTitle());
          $unit_price = $this->chainPriceResolver->resolve($purchased_entity, $order_item->getQuantity());
          $order_item->setUnitPrice($unit_price);
        }

        $order_item->save();
      }

      foreach ($this->processors as $processor) {
        $processor->process($order);
      }

      $order->save();
      unset(self::$refreshingOrders[$order->id()]);
    }
  }

}
