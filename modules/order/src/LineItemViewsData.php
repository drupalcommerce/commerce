<?php

/**
 * @file
 * Contains \Drupal\commerce_order\LineItemViewsData.
 */

namespace Drupal\commerce_order;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for line items.
 */
class LineItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Unset the default purchased entity relationship.
    // It does not work properly, the target type it is not defined.
    unset($data['commerce_line_item']['purchased_entity']['relationship']);

    // Collect all purchasable entity types.
    $line_item_types = $this->entityManager->getStorage('commerce_line_item_type')->loadMultiple();
    $entity_type_ids = [];
    /** @var \Drupal\commerce_order\Entity\LineItemTypeInterface $line_item_type */
    foreach ($line_item_types as $line_item_type) {
      if ($entity_type_id = $line_item_type->getPurchasableEntityType()) {
        $entity_type_ids[] = $entity_type_id;
      }
    }
    $entity_type_ids = array_unique($entity_type_ids);

    // Provide a relationship for each entity type found.
    foreach ($entity_type_ids as $entity_type_id) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $data['commerce_line_item'][$entity_type_id] = [
        'relationship' => [
          'title' => $entity_type->getLabel(),
          'help' => t('The purchased @entity_type.', ['@entity_type' => $entity_type->getLowercaseLabel()]),
          'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'purchased_entity',
          'id' => 'standard',
          'label' => $entity_type->getLabel(),
        ],
      ];
    }

    return $data;
  }

}
