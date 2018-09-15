<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product variation type condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_variation_type",
 *   label = @Translation("Product variation type"),
 *   display_label = @Translation("Product variation types"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemVariationType extends ConditionBase {

  use VariationTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }

    return in_array($purchased_entity->bundle(), $this->configuration['variation_types']);
  }

}
