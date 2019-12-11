<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface;
use Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface;
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
   * The chain tax rate resolver.
   *
   * @var \Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface
   */
  protected $chainRateResolver;

  /**
   * The store's tax zones, keyed by store ID.
   *
   * @var array
   */
  protected $storeZones = [];

  /**
   * The loaded tax types.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface[]
   */
  protected $taxTypes = [];

  /**
   * A cache of instantiated store profiles.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $storeProfiles = [];

  /**
   * Constructs a new TaxOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface $chain_rate_resolver
   *   The chain tax rate resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RounderInterface $rounder, ChainTaxRateResolverInterface $chain_rate_resolver) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rounder = $rounder;
    $this->chainRateResolver = $chain_rate_resolver;
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
          $rates = $this->getDefaultRates($order_item, $store);
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
   * Gets the default tax rates for the given order item and store.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_tax\TaxRate[]
   *   The tax rates, keyed by tax zone ID.
   */
  protected function getDefaultRates(OrderItemInterface $order_item, StoreInterface $store) {
    $store_profile = $this->buildStoreProfile($store);
    $rates = [];
    foreach ($this->getStoreZones($store) as $zone) {
      $rate = $this->chainRateResolver->resolve($zone, $order_item, $store_profile);
      if (is_object($rate)) {
        $rates[$zone->getId()] = $rate;
      }
    }

    return $rates;
  }

  /**
   * Gets the tax zones for the given store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_tax\TaxZone[]
   *   The tax zones.
   */
  protected function getStoreZones(StoreInterface $store) {
    $store_id = $store->id();
    if (!isset($this->storeZones[$store_id])) {
      $tax_types = $this->getTaxTypes();
      $tax_types = array_filter($tax_types, function (TaxTypeInterface $tax_type) {
        $tax_type_plugin = $tax_type->getPlugin();
        return ($tax_type_plugin instanceof LocalTaxTypeInterface) && $tax_type_plugin->isDisplayInclusive();
      });

      $this->storeZones[$store_id] = [];
      $store_address = $store->getAddress();
      foreach ($tax_types as $tax_type) {
        /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $tax_type_plugin */
        $tax_type_plugin = $tax_type->getPlugin();
        foreach ($tax_type_plugin->getZones() as $zone) {
          if ($zone->match($store_address)) {
            $this->storeZones[$store_id][] = $zone;
          }
        }
        // Assume that only a single tax type's zones will match.
        if (count($this->storeZones[$store_id]) > 0) {
          break;
        }
      }
    }

    return $this->storeZones[$store_id];
  }

  /**
   * Builds a customer profile for the given store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The customer profile.
   */
  protected function buildStoreProfile(StoreInterface $store) {
    $store_id = $store->id();
    if (!isset($this->storeProfiles[$store_id])) {
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $this->storeProfiles[$store_id] = $profile_storage->create([
        'type' => 'customer',
        'uid' => 0,
        'address' => $store->getAddress(),
      ]);
    }

    return $this->storeProfiles[$store_id];
  }

  /**
   * Gets the available tax types.
   *
   * @return \Drupal\commerce_tax\Entity\TaxTypeInterface[]
   *   The tax types.
   */
  protected function getTaxTypes() {
    if (empty($this->taxTypes)) {
      $tax_type_storage = $this->entityTypeManager->getStorage('commerce_tax_type');
      $this->taxTypes = $tax_type_storage->loadByProperties(['status' => TRUE]);
      uasort($this->taxTypes, [TaxType::class, 'sort']);
    }

    return $this->taxTypes;
  }

}
