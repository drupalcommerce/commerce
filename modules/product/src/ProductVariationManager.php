<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductVariationManager.
 */

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductVariationManager implements ProductVariationManagerInterface {

  /**
   * The variation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a ProductVariationManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher) {
    $this->variationStorage = $entityManager->getStorage('commerce_product_variation');
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledVariations(ProductInterface $product) {
    $ids = [];
    foreach($product->variations as $variation) {
      $ids[] = $variation->target_id;
    }

    $query = \Drupal::entityQuery('commerce_product_variation')
      ->condition('status', TRUE)
      ->condition('variation_id', $ids, "IN");
    $result = $query->execute();

    if(empty($result)){
      return [];
    }

    $enabledVariations = $this->variationStorage->loadMultiple($result);
    $event = new FilterVariationsEvent($product, $enabledVariations);
    $this->eventDispatcher->dispatch(ProductEvents::FILTER_VARIATIONS, $event);
    $enabledVariations = $event->getVariations();

    return $enabledVariations;
  }

}
