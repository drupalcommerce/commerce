<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Resolver\DefaultStoreResolver.
 */

namespace Drupal\commerce_store\Resolver;

use Drupal\Core\Entity\EntityManagerInterface;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->storage = $entityManager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    return $this->storage->loadDefault();
  }

}
