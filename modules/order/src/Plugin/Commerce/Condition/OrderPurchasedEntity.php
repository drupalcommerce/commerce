<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the variation condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_purchased_entity",
 *   label = @Translation("Purchased entity"),
 *   display_label = @Translation("Order contains specific purchased item"),
 *   category = @Translation("Purchased items"),
 *   entity_type = "commerce_order",
 *   weight = -1,
 *   deriver = "Drupal\commerce_order\Plugin\Commerce\Condition\PurchasedEntityConditionDeriver"
 * )
 */
class OrderPurchasedEntity extends PurchasedEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    assert($entity instanceof OrderInterface);
    foreach ($entity->getItems() as $order_item) {
      if ($this->isValid($order_item->getPurchasedEntity())) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
