<?php

namespace Drupal\commerce_product;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default implementation of the OrderItemTypeMapInterface.
 */
class OrderItemTypeMap implements OrderItemTypeMapInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The product type ID <-> order item type ID map.
   *
   * @var array
   */
  protected $map;

  /**
   * Constructs a new OrderItemTypeMap object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CacheBackendInterface $cache, EntityTypeManagerInterface $entity_type_manager) {
    $this->cache = $cache;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId($product_type_id) {
    if (!isset($this->map)) {
      if ($cached_map = $this->cache->get('commerce_product.order_item_type_map')) {
        $this->map = $cached_map->data;
      }
      else {
        $this->map = $this->buildMap();
        $this->cache->set('commerce_product.order_item_type_map', $this->map);
      }
    }
    // A valid product type ID should always have a matching order item type ID.
    if (empty($this->map[$product_type_id])) {
      throw new \InvalidArgumentException(sprintf('No order item type found for the "%s" product type.', $product_type_id));
    }

    return $this->map[$product_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    $this->map = NULL;
    $this->cache->delete('commerce_product.order_item_type_map');
  }

  /**
   * Builds the product type ID <-> order item type ID map.
   *
   * @throws \Exception
   *   Thrown when the product type references an invalid variation type, or
   *   when the variation type does not reference an order item type.
   *
   * @return array
   *   The built map.
   */
  protected function buildMap() {
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    $variation_type_storage = $this->entityTypeManager->getStorage('commerce_product_variation_type');
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface[] $product_types */
    $product_types = $product_type_storage->loadMultiple();
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface[] $variation_types */
    $variation_types = $variation_type_storage->loadMultiple();

    $map = [];
    foreach ($product_types as $product_type_id => $product_type) {
      $variation_type_id = $product_type->getVariationTypeId();
      if (!isset($variation_types[$variation_type_id])) {
        throw new \Exception(sprintf('The "%s" product type references an invalid variation type.', $product_type_id));
      }
      $variation = $variation_types[$variation_type_id];
      $order_item_type_id = $variation->getOrderItemTypeId();
      if (empty($order_item_type_id)) {
        throw new \Exception(sprintf('The "%s" product variation type does not reference an order item type.', $variation_type_id));
      }

      $map[$product_type_id] = $order_item_type_id;
    }

    return $map;
  }

}
