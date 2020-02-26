<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface;
use Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class StoreTax implements StoreTaxInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The chain tax rate resolver.
   *
   * @var \Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface
   */
  protected $chainRateResolver;

  /**
   * The store tax types, keyed by store ID.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface[]
   */
  protected $storeTaxTypes = [];

  /**
   * The loaded tax types.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface[]
   */
  protected $taxTypes = [];

  /**
   * The instantiated store profiles.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $storeProfiles = [];

  /**
   * Constructs a new StoreTax object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface $chain_rate_resolver
   *   The chain tax rate resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ChainTaxRateResolverInterface $chain_rate_resolver) {
    $this->entityTypeManager = $entity_type_manager;
    $this->chainRateResolver = $chain_rate_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultTaxType(StoreInterface $store) {
    $store_id = $store->id();
    if (!array_key_exists($store_id, $this->storeTaxTypes)) {
      $store_address = $store->getAddress();
      $tax_types = $this->getTaxTypes();
      $this->storeTaxTypes[$store_id] = NULL;
      foreach ($tax_types as $tax_type) {
        /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $tax_type_plugin */
        $tax_type_plugin = $tax_type->getPlugin();
        $matching_zones = $tax_type_plugin->getMatchingZones($store_address);
        if ($matching_zones) {
          $this->storeTaxTypes[$store_id] = $tax_type;
          break;
        }
      }
    }

    return $this->storeTaxTypes[$store_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultZones(StoreInterface $store) {
    $tax_type = $this->getDefaultTaxType($store);
    if (!$tax_type) {
      return [];
    }
    /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $tax_type_plugin */
    $tax_type_plugin = $tax_type->getPlugin();
    $zones = $tax_type_plugin->getMatchingZones($store->getAddress());

    return $zones;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRates(StoreInterface $store, OrderItemInterface $order_item) {
    $tax_type = $this->getDefaultTaxType($store);
    if (!$tax_type) {
      return [];
    }
    $store_profile = $this->buildStoreProfile($store);
    $rates = [];
    foreach ($this->getDefaultZones($store) as $zone) {
      $this->chainRateResolver->setTaxType($tax_type);
      $rate = $this->chainRateResolver->resolve($zone, $order_item, $store_profile);
      if (is_object($rate)) {
        $rates[$zone->getId()] = $rate;
      }
    }

    return $rates;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCaches() {
    $this->taxTypes = [];
    $this->storeTaxTypes = [];
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
      /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface[] $tax_types */
      $this->taxTypes = $tax_type_storage->loadMultiple();
      foreach ($this->taxTypes as $tax_type_id => $tax_type) {
        if (!$tax_type->status()) {
          unset($this->taxTypes[$tax_type_id]);
        }
        $tax_type_plugin = $tax_type->getPlugin();
        if (!($tax_type_plugin instanceof LocalTaxTypeInterface) || !$tax_type_plugin->isDisplayInclusive()) {
          unset($this->taxTypes[$tax_type_id]);
        }
      }
      uasort($this->taxTypes, [TaxType::class, 'sort']);
    }

    return $this->taxTypes;
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

}
