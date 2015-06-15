<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Resolver\DefaultStoreResolver.
 */

namespace Drupal\commerce_store\Resolver;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Returns the default store, if set.
 */
class DefaultStoreResolver implements StoreResolverInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new DefaultStoreResolver object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityManagerInterface $entityManager) {
    $this->configFactory = $configFactory;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $store = NULL;
    $uuid = $this->configFactory->get('commerce_store.settings')->get('default_store');
    if ($uuid) {
      $store = $this->entityManager->loadEntityByUuid('commerce_store', $uuid);
    }

    return $store;
  }

}
