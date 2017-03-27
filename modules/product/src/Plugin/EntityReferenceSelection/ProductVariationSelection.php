<?php

namespace Drupal\commerce_product\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the file entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:commerce_product_variation",
 *   label = @Translation("Variation selection"),
 *   entity_types = {"commerce_product_variation"},
 *   group = "default",
 *   weight = 1
 * )
 */
class ProductVariationSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface[] $variation_types */
    $variation_types = $this->entityManager->getStorage('commerce_product_variation_type')->loadMultiple();
    $options = array();
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $variation_types[$entity->bundle()];
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
      $entity = $this->entityManager->getTranslationFromContext($entity);

      if ($bundle->shouldGenerateTitle()) {
        $option_label = $entity->label();
      }
      else {
        $option_label = $entity->getProduct()->label() . ': ' . $entity->label();
      }

      $options[$bundle->id()][$entity_id] = Html::escape($option_label);
    }

    return $options;
  }

}
