<?php

namespace Drupal\commerce_product;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the product variation storage.
 */
class ProductVariationStorage extends CommerceContentEntityStorage implements ProductVariationStorageInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new ProductVariationStorage object.
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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher, RequestStack $request_stack) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $event_dispatcher);

    $this->requestStack = $request_stack;
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
      $container->get('event_dispatcher'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySku($sku) {
    $variations = $this->loadByProperties(['sku' => $sku]);
    $variation = reset($variations);

    return $variation ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromContext(ProductInterface $product) {
    $current_request = $this->requestStack->getCurrentRequest();
    if ($variation_id = $current_request->query->get('v')) {
      if (in_array($variation_id, $product->getVariationIds())) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
        $variation = $this->load($variation_id);
        if ($variation->isActive() && $variation->access('view')) {
          return $variation;
        }
      }
    }
    return $product->getDefaultVariation();
  }

  /**
   * {@inheritdoc}
   */
  public function loadEnabled(ProductInterface $product) {
    $ids = [];
    foreach ($product->variations as $variation) {
      $ids[$variation->target_id] = $variation->target_id;
    }
    // Speed up loading by filtering out the IDs of disabled variations.
    $query = $this->getQuery()
      ->addTag('entity_access')
      ->condition('status', TRUE)
      ->condition('variation_id', $ids, 'IN');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    // Restore the original sort order.
    $result = array_intersect_key($ids, $result);

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $enabled_variation */
    $enabled_variations = $this->loadMultiple($result);
    // Allow modules to apply own filtering (based on date, stock, etc).
    $event = new FilterVariationsEvent($product, $enabled_variations);
    $this->eventDispatcher->dispatch(ProductEvents::FILTER_VARIATIONS, $event);
    $enabled_variations = $event->getVariations();
    // Filter out variations that can't be accessed.
    foreach ($enabled_variations as $variation_id => $enabled_variation) {
      if (!$enabled_variation->access('view')) {
        unset($enabled_variations[$variation_id]);
      }
    }

    return $enabled_variations;
  }

}
