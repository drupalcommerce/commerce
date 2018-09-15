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

    // Provide a relationship for each entity type found.
    foreach ($entity_type_ids as $entity_type_id) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $data['commerce_order_item'][$entity_type_id] = [
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
