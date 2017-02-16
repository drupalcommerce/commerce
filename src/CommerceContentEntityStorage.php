<?php

namespace Drupal\commerce;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The default Commerce storage for content entities.
 *
 * Fires matching events for entity hooks.
 */
class CommerceContentEntityStorage extends SqlContentEntityStorage {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CommerceContentEntityStorage object.
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
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);

    $this->eventDispatcher = $event_dispatcher;
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
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function postLoad(array &$entities) {
    parent::postLoad($entities);

    $event_class = $this->entityType->getHandlerClass('event');
    if (!$event_class) {
      return;
    }
    // hook_entity_load() is invoked for all entities at once.
    // The event is dispatched for each entity separately, for better DX.
    // @todo Evaluate performance implications.
    $event_name = $this->getEventName('load');
    foreach ($entities as $entity) {
      $this->eventDispatcher->dispatch($event_name, new $event_class($entity));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    parent::invokeHook($hook, $entity);

    $event_class = $this->entityType->getHandlerClass('event');
    if ($event_class) {
      $this->eventDispatcher->dispatch($this->getEventName($hook), new $event_class($entity));
    }
  }

  /**
   * Gets the event name for the given hook.
   *
   * Created using the the entity type's module name and ID.
   * For example, the 'presave' hook for commerce_order_item entities maps
   * to the 'commerce_order.commerce_order_item.presave' event name.
   *
   * @param string $hook
   *   One of 'load', 'create', 'presave', 'insert', 'update', 'predelete',
   *   'delete', 'translation_insert', 'translation_delete'.
   *
   * @return string
   *   The event name.
   */
  protected function getEventName($hook) {
    return $this->entityType->getProvider() . '.' . $this->entityType->id() . '.' . $hook;
  }

}
