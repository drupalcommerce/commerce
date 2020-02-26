<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_tax\Entity\TaxType;
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
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The store tax.
   *
   * @var \Drupal\commerce_tax\StoreTaxInterface
   */
  protected $storeTax;

  /**
   * Constructs a new TaxOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_tax\StoreTaxInterface $store_tax
   *   The store tax.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RounderInterface $rounder, StoreTaxInterface $store_tax) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rounder = $rounder;
    $this->storeTax = $store_tax;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $tax_types = $this->getTaxTypes();
    foreach ($tax_types as $tax_type) {
      if ($tax_type->getPlugin()->applies($order)) {
        $tax_type->getPlugin()->apply($order);
      }
    }
    // Don't overcharge a tax-exempt customer if the price is tax-inclusive.
    // For example, a 12 EUR price with 20% EU VAT gets reduced to 10 EUR
    // when selling to customers outside the EU, but only if no other tax
    // was applied (e.g. a Japanese customer paying Japanese tax due to the
    // store being registered to collect tax there).
    $calculation_date = $order->getCalculationDate();
    $store = $order->getStore();
    if ($store->get('prices_include_tax')->value) {
      foreach ($order->getItems() as $order_item) {
        $tax_adjustments = array_filter($order_item->getAdjustments(), function ($adjustment) {
          /** @var \Drupal\commerce_order\Adjustment $adjustment */
          return $adjustment->getType() == 'tax';
        });
        if (empty($tax_adjustments)) {
          $unit_price = $order_item->getUnitPrice();
          $rates = $this->storeTax->getDefaultRates($store, $order_item);
          foreach ($rates as $rate) {
            $percentage = $rate->getPercentage($calculation_date);
            $tax_amount = $percentage->calculateTaxAmount($order_item->getUnitPrice(), TRUE);
            $tax_amount = $this->rounder->round($tax_amount);
            $unit_price = $unit_price->subtract($tax_amount);
          }
          $order_item->setUnitPrice($unit_price, $order_item->isUnitPriceOverridden());
        }
      }
    }
  }

  /**
   * Gets the available tax types.
   *
   * @return \Drupal\commerce_tax\Entity\TaxTypeInterface[]
   *   The tax types.
   */
  protected function getTaxTypes() {
    $tax_type_storage = $this->entityTypeManager->getStorage('commerce_tax_type');
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface[] $tax_types */
    $tax_types = $tax_type_storage->loadByProperties(['status' => TRUE]);
    uasort($tax_types, [TaxType::class, 'sort']);

    return $tax_types;
  }

}
