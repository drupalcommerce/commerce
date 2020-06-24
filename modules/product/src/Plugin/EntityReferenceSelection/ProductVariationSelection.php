<?php

namespace Drupal\commerce_product\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Enables product variation selection by title or SKU.
 *
 * @EntityReferenceSelection(
 *   id = "default:commerce_product_variation",
 *   label = @Translation("Product variation selection"),
 *   entity_types = {"commerce_product_variation"},
 *   group = "default",
 *   weight = 1
 * )
 */
class ProductVariationSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();

    $query = $this->entityTypeManager->getStorage('commerce_product_variation')->getQuery();

    if (!empty($configuration['target_bundles'])) {
      $query->condition('type', $configuration['target_bundles'], 'IN');
    }

    if (isset($match)) {
      $match_condition = $query->orConditionGroup()
        ->condition('title', $match, $match_operator)
        ->condition('sku', $match, $match_operator);
      $query->condition($match_condition);
    }

    // Add entity-access tag.
    $query->addTag('commerce_product_variation_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if ($configuration['sort']['field'] !== '_none') {
      $query->sort($configuration['sort']['field'], $configuration['sort']['direction']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage('commerce_product_variation')->loadMultiple($result);
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($entity->getSku() . ': ' . $this->entityRepository->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

}
