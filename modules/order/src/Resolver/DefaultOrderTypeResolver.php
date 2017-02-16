<?php

namespace Drupal\commerce_order\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the order type, based on order item type configuration.
 */
class DefaultOrderTypeResolver implements OrderTypeResolverInterface {

  /**
   * The order item type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderItemTypeStorage;

  /**
   * Constructs a new DefaultOrderTypeResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->orderItemTypeStorage = $entity_type_manager->getStorage('commerce_order_item_type');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OrderItemInterface $order_item) {
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    $order_item_type = $this->orderItemTypeStorage->load($order_item->bundle());

    return $order_item_type->getOrderTypeId();
  }

}
