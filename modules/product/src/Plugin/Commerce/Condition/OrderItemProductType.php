<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product type condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_product_type",
 *   label = @Translation("Product type"),
 *   display_label = @Translation("Product types"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemProductType extends ConditionBase {

  use ProductTypeTrait;

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
    $product_type = $purchased_entity->getProduct()->bundle();

    return in_array($product_type, $this->configuration['product_types']);
  }

}
