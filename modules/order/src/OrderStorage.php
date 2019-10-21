<?php

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the order storage.
 */
class OrderStorage extends CommerceContentEntityStorage {

  /**
   * The order refresh.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

  /**
   * Whether the order refresh should be skipped.
   *
   * @var bool
   */
  protected $skipRefresh = FALSE;

  /**
   * Constructs a new OrderStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_order\OrderRefreshInterface $order_refresh
   *   The order refresh process.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, OrderRefreshInterface $order_refresh) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager, $event_dispatcher);

    $this->orderRefresh = $order_refresh;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_order.order_refresh')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id) {
    // This method is used by the entity save process, triggering an order
    // refresh would cause a save-within-a-save.
    $this->skipRefresh = TRUE;
    $unchanged_order = parent::loadUnchanged($id);
    $this->skipRefresh = FALSE;
    return $unchanged_order;
  }

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    if ($hook == 'presave') {
      // Order::preSave() has completed, now run the storage-level pre-save
      // tasks. These tasks can modify the order, so they need to run
      // before the entity/field hooks are invoked.
      $this->doOrderPreSave($entity);
    }

    parent::invokeHook($hook, $entity);
  }

  /**
   * Performs order-specific pre-save tasks.
   *
   * This includes:
   * - Refreshing the order.
   * - Recalculating the total price.
   * - Dispatching the "order paid" event.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function doOrderPreSave(OrderInterface $order) {
    if ($order->getRefreshState() == OrderInterface::REFRESH_ON_SAVE) {
      $this->orderRefresh->refresh($order);
    }
    // Only the REFRESH_ON_LOAD state needs to be persisted on the entity.
    if ($order->getRefreshState() != OrderInterface::REFRESH_ON_LOAD) {
      $order->setRefreshState(NULL);
    }
    $order->recalculateTotalPrice();

    // Notify other modules if the order has been fully paid.
    $original_paid = isset($order->original) ? $order->original->isPaid() : FALSE;
    if ($order->isPaid() && !$original_paid) {
      // Order::preSave() initializes the 'paid_event_dispatched' flag to FALSE.
      // Skip dispatch if it already happened once (flag is TRUE), or if the
      // order was completed before Commerce 8.x-2.10 (flag is NULL).
      if ($order->getData('paid_event_dispatched') === FALSE) {
        $event = new OrderEvent($order);
        $this->eventDispatcher->dispatch(OrderEvents::ORDER_PAID, $event);
        $order->setData('paid_event_dispatched', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function postLoad(array &$entities) {
    if (!$this->skipRefresh) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface[] $entities */
      foreach ($entities as $entity) {
        $explicitly_requested = $entity->getRefreshState() == OrderInterface::REFRESH_ON_LOAD;
        if ($explicitly_requested || $this->orderRefresh->shouldRefresh($entity)) {
          // Reuse the doPostLoad logic.
          $entity->setRefreshState(OrderInterface::REFRESH_ON_SAVE);
          $entity->save();
        }
      }
    }

    return parent::postLoad($entities);
  }

}
