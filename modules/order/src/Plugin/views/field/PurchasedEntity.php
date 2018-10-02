<?php

namespace Drupal\commerce_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\Field;
use Drupal\views\ResultRow;

/**
 * Custom handler for order item purchased_entity field.
 *
 * @ViewsField("commerce_order_item_purchased_entity")
 */
class PurchasedEntity extends Field {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->getEntity($values);
    if ($order_item->getPurchasedEntityId()) {
      return parent::getItems($values);
    }
    else {
      // Use the order item title for not purchasable order items.
      return [['rendered' => $order_item->getTitle()]];
    }
  }

}
