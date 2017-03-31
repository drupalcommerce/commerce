<?php

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_order\OrderRefreshInterface $order_refresh
   *   The order refresh process.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher, OrderRefreshInterface $order_refresh) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $event_dispatcher);
    $this->orderRefresh = $order_refresh;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
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
  protected function doPreSave(EntityInterface $entity) {
    $id = parent::doPreSave($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
    if ($entity->getRefreshState() == OrderInterface::REFRESH_ON_SAVE) {
      $this->orderRefresh->refresh($entity);
    }
    // Only the REFRESH_ON_LOAD state needs to be persisted on the entity.
    if ($entity->getRefreshState() != OrderInterface::REFRESH_ON_LOAD) {
      $entity->setRefreshState(NULL);
    }
    $entity->recalculateTotalPrice();

    return $id;
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
