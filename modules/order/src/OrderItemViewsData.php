<?php

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceEntityViewsData;

/**
 * Provides views data for order items.
 */
class OrderItemViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Unset the default purchased entity relationship.
    // It does not work properly, the target type it is not defined.
    unset($data['commerce_order_item']['purchased_entity']['relationship']);

    // Collect all purchasable entity types.
    $order_item_types = $this->entityManager->getStorage('commerce_order_item_type')->loadMultiple();
    $entity_type_ids = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    foreach ($order_item_types as $order_item_type) {
      if ($entity_type_id = $order_item_type->getPurchasableEntityTypeId()) {
        $entity_type_ids[] = $entity_type_id;
      }
    }
    $entity_type_ids = array_unique($entity_type_ids);
    $table_mapping = $this->storage->getTableMapping();

    // Provide a relationship for each entity type found.
    foreach ($entity_type_ids as $entity_type_id) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $data['commerce_order_item'][$entity_type_id] = [
        'relationship' => [
          'title' => $entity_type->getLabel(),
          'help' => t('The purchased @entity_type.', ['@entity_type' => $entity_type->getLowercaseLabel()]),
          'base' => $this->getViewsTableForEntityType($entity_type),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => $table_mapping->getColumnNames('purchased_entity')['target_id'],
          'id' => 'standard',
          'label' => $entity_type->getLabel(),
        ],
      ];

      $target_base_table = $this->getViewsTableForEntityType($entity_type);
      $data[$target_base_table]['reverse__commerce_order_item__purchased_entity'] = [
        'relationship' => [
          'title' => $this->entityType->getLabel(),
          'help' => t('The @order_item_entity_type for this @entity_type.', [
            '@order_item_entity_type' => $this->entityType->getPluralLabel(),
            '@entity_type' => $entity_type->getLowercaseLabel(),
          ]),
          'group' => $entity_type->getLabel(),
          'base' => $this->getViewsTableForEntityType($this->entityType),
          'base field' => $table_mapping->getColumnNames('purchased_entity')['target_id'],
          'relationship field' => $entity_type->getKey('id'),
          'id' => 'standard',
          'label' => $this->entityType->getLabel(),
          'entity_type' => $this->entityType->id(),
        ],
      ];
    }

    return $data;
  }

}
