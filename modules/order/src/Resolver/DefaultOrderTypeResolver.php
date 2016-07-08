<?php

namespace Drupal\commerce_order\Resolver;
use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the order type, based on line item type configuration.
 */
class DefaultOrderTypeResolver implements OrderTypeResolverInterface {

  /**
   * The line item type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $lineItemTypeStorage;

  /**
   * Constructs a new DefaultOrderTypeResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->lineItemTypeStorage = $entity_type_manager->getStorage('commerce_line_item_type');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(LineItemInterface $line_item) {
    /** @var \Drupal\commerce_order\Entity\LineItemTypeInterface $line_item_type */
    $line_item_type = $this->lineItemTypeStorage->load($line_item->bundle());

    return $line_item_type->getOrderTypeId();
  }

}
