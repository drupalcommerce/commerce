<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

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
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The order processors.
   *
   * @var \Drupal\commerce_order\OrderProcessorInterface[]
   */
  protected $processors = [];

  /**
   * Constructs a new OrderRefresh object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ChainPriceResolverInterface $chain_price_resolver, AccountInterface $current_user, TimeInterface $time) {
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->chainPriceResolver = $chain_price_resolver;
    $this->currentUser = $current_user;
    $this->time = $time;
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
  public function shouldRefresh(OrderInterface $order) {
    if (!$this->needsRefresh($order)) {
      return FALSE;
    }
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());
    // Ensure the order is only refreshed for its customer, when configured so.
    if ($order_type->getRefreshMode() == OrderType::REFRESH_CUSTOMER) {
      if ($order->getCustomerId() != $this->currentUser->id()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function needsRefresh(OrderInterface $order) {
    // Only draft orders should be automatically refreshed.
    if ($order->getState()->getId() != 'draft') {
      return FALSE;
    }

    // Only unlocked orders should be automatically refreshed.
    if ($order->isLocked()) {
      return FALSE;
    }

    // Accommodate long-running processes by always using the current time.
    $current_time = $this->time->getCurrentTime();
    $order_time = $order->getChangedTime();
    if (date('Y-m-d', $current_time) != date('Y-m-d', $order_time)) {
      // Refresh on a date change regardless of the refresh frequency.
      // Date changes can impact tax rate amounts, availability of promotions.
      return TRUE;
    }
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());
    $refreshed_ago = $current_time - $order_time;
    if ($refreshed_ago >= $order_type->getRefreshFrequency()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(OrderInterface $order) {
    $current_time = $this->time->getCurrentTime();
    $order->setChangedTime($current_time);
    $order->clearAdjustments();
    // Nothing else can be done while the order is empty.
    if (!$order->hasItems()) {
      return;
    }

    $time = $order->getCalculationDate()->format('U');
    $context = new Context($order->getCustomer(), $order->getStore(), $time);
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity) {
        $order_item->setTitle($purchased_entity->getOrderItemTitle());
        if (!$order_item->isUnitPriceOverridden()) {
          $unit_price = $this->chainPriceResolver->resolve($purchased_entity, $order_item->getQuantity(), $context);
          $order_item->setUnitPrice($unit_price);
        }
      }
      // If the order refresh is running during order preSave(),
      // $order_item->getOrder() will point to the original order (or
      // NULL, in case the order item is new).
      $order_item->order_id->entity = $order;
    }

    // Allow the processors to modify the order and its items.
    foreach ($this->processors as $processor) {
      $processor->process($order);
      if (!$order->hasItems()) {
        return;
      }
    }

    foreach ($order->getItems() as $order_item) {
      if ($order_item->hasTranslationChanges()) {
        // Remove the order that was set above, to avoid
        // crashes during the entity save process.
        $order_item->order_id->entity = NULL;
        $order_item->setChangedTime($current_time);
        $order_item->save();
      }
    }
  }

}
