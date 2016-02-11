<?php

namespace Drupal\commerce_store\Resolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the default store, if known.
 */
class DefaultStoreResolver implements StoreResolverInterface {

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new DefaultStoreResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    return $this->storage->loadDefault();
  }

}
