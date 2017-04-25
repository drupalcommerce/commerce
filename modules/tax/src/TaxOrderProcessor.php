<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Applies taxes to orders during the order refresh process.
 */
class TaxOrderProcessor implements OrderProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TaxOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $tax_type_storage = $this->entityTypeManager->getStorage('commerce_tax_type');
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface[] $tax_types */
    $tax_types = $tax_type_storage->loadMultiple();
    foreach ($tax_types as $tax_type) {
      if ($tax_type->getPlugin()->applies($order)) {
        $tax_type->getPlugin()->apply($order);
      }
    }
  }

}
