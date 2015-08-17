<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceEntityStorage.
 */

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
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeInterface $entityType, Connection $database, EntityManagerInterface $entityManager, CacheBackendInterface $cache, LanguageManagerInterface $languageManager, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($entityType, $database, $entityManager, $cache, $languageManager);

    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
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

    $eventClass = $this->entityType->getHandlerClass('event');
    if (!$eventClass) {
      return;
    }
    // hook_entity_load() is invoked for all entities at once.
    // The event is dispatched for each entity separately, for better DX.
    // @todo Evaluate performance implications.
    $eventName = $this->getEventName('load');
    foreach ($entities as $entity) {
      $this->eventDispatcher->dispatch($eventName, new $eventClass($entity));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    parent::invokeHook($hook, $entity);

    $eventClass = $this->entityType->getHandlerClass('event');
    if ($eventClass) {
      $this->eventDispatcher->dispatch($this->getEventName($hook), new $eventClass($entity));
    }
  }

  /**
   * Gets the event name for the given hook.
   *
   * Created using the the entity type's module name and id.
   * For example, the 'presave' hook for commerce_line_item entities maps
   * to the 'commerce_order.commerce_line_item.presave' event name.
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
