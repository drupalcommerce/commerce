<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the product type condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_product_type",
 *   label = @Translation("Product type"),
 *   display_label = @Translation("Order contains product types"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderProductType extends ConditionBase {

  use ProductTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    foreach ($order->getItems() as $order_item) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_product_variation') {
        continue;
      }
      $product_type = $purchased_entity->getProduct()->bundle();
      if (in_array($product_type, $this->configuration['product_types'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
