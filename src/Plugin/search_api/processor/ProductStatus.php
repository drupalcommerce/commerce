<?php

namespace Drupal\commerce\Plugin\search_api\processor;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Excludes unpublished nodes from node indexes.
 *
 * @SearchApiProcessor(
 *   id = "commerce_product_status",
 *   label = @Translation("Product status"),
 *   description = @Translation("Exclude unpublished products from being indexed."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class ProductStatus extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $supported_entity_types = ['commerce_product', 'commerce_product_variation'];
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), $supported_entity_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $enabled = TRUE;
      if ($object instanceof ProductInterface) {
        $enabled = $object->isPublished();
      }
      elseif ($object instanceof ProductVariationInterface) {
        $enabled = $object->isActive() && $object->getProduct()->isPublished();
      }
      if (!$enabled) {
        unset($items[$item_id]);
      }
    }
  }

}
