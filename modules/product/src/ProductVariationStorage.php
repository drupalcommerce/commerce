<?php

namespace Drupal\commerce_product;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;

/**
 * Defines the product variation storage.
 */
class ProductVariationStorage extends CommerceContentEntityStorage implements ProductVariationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadEnabled(ProductInterface $product) {
    $ids = [];
    foreach($product->variations as $variation) {
      $ids[$variation->target_id] = $variation->target_id;
    }
    // Speed up loading by filtering out the IDs of disabled variations.
    $query = $this->getQuery()
      ->condition('status', TRUE)
      ->condition('variation_id', $ids, 'IN');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    // Restore the original sort order.
    $result = array_intersect_key($ids, $result);

    $enabled_variations = $this->loadMultiple($result);
    // Allow modules to apply own filtering (based on date, stock, etc).
    $event = new FilterVariationsEvent($product, $enabled_variations);
    $this->eventDispatcher->dispatch(ProductEvents::FILTER_VARIATIONS, $event);
    $enabled_variations = $event->getVariations();

    return $enabled_variations;
  }

}
