<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreStorage.
 */

namespace Drupal\commerce_store;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the store storage.
 */
class StoreStorage extends CommerceContentEntityStorage implements StoreStorageInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new StoreStorage object.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(EntityTypeInterface $entityType, Connection $database, EntityManagerInterface $entityManager, CacheBackendInterface $cache, LanguageManagerInterface $languageManager, EventDispatcherInterface $eventDispatcher, ConfigFactoryInterface $configFactory) {
    parent::__construct($entityType, $database, $entityManager, $cache, $languageManager, $eventDispatcher);

    $this->configFactory = $configFactory;
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
      $container->get('event_dispatcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefault() {
    $default = NULL;
    $uuid = $this->configFactory->get('commerce_store.settings')->get('default_store');
    if ($uuid) {
      $entities = $this->loadByProperties(['uuid' => $uuid]);
      $default = reset($entities);
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function markAsDefault(StoreInterface $store) {
    $config = $this->configFactory->getEditable('commerce_store.settings');
    if ($config->get('default_store') != $store->uuid()) {
      $config->set('default_store', $store->uuid());
      $config->save();
    }
  }

}
