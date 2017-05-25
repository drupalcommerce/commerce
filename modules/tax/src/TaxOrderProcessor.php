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
      if ($tax_type->status() && $tax_type->getPlugin()->applies($order)) {
        $tax_type->getPlugin()->apply($order);
      }
    }
    // Tax types can create a negative adjustment when a price includes
    // tax, but the customer is tax-exempt. These negative adjustments
    // are removed and applied directly to the unit price, so that the
    // customer always sees the actual price they are being charged.
    // @todo Figure out if this conversion should be optional/configurable.
    if ($order->getStore()->get('prices_include_tax')->value) {
      foreach ($order->getItems() as $order_item) {
        $adjustments = $order_item->getAdjustments();
        $negative_tax_adjustments = array_filter($adjustments, function ($adjustment) {
          /** @var \Drupal\commerce_order\Adjustment $adjustment */
          return $adjustment->getType() == 'tax' && $adjustment->isNegative();
        });
        $adjustments = array_diff_key($adjustments, $negative_tax_adjustments);
        $unit_price = $order_item->getUnitPrice();
        foreach ($negative_tax_adjustments as $adjustment) {
          $unit_price = $unit_price->add($adjustment->getAmount());
        }
        $order_item->setUnitPrice($unit_price);
        $order_item->setAdjustments($adjustments);
      }
    }
  }

}
