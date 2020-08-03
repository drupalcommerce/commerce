<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the variation condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_purchased_entity",
 *   label = @Translation("Purchased entity"),
 *   display_label = @Translation("Specific purchased item"),
 *   category = @Translation("Purchased items"),
 *   entity_type = "commerce_order_item",
 *   weight = -1,
 *   deriver = "Drupal\commerce_order\Plugin\Commerce\Condition\PurchasedEntityConditionDeriver"
 * )
 */
class OrderItemPurchasedEntity extends PurchasedEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    assert($entity instanceof OrderItemInterface);
    return $this->isValid($entity->getPurchasedEntity());
  }

}
