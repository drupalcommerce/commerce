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
   * A config object for the commerce_store configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

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
    $this->config = $configFactory->get('commerce_store.settings');
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $store = NULL;
    $uuid = $this->config->get('default_store');
    if ($uuid) {
      $store = $this->entityManager->loadEntityByUuid('commerce_store', $uuid);
    }

    return $store;
  }

}
