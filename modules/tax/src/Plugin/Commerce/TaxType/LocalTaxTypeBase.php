<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use CommerceGuys\Addressing\Address;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the base class for local tax types.
 */
abstract class LocalTaxTypeBase extends TaxTypeBase implements LocalTaxTypeInterface {

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
   * The zones.
   *
   * @var \Drupal\commerce_tax\TaxZone[]
   */
  protected $zones;

  /**
   * Constructs a new LocalTaxTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface $chain_rate_resolver
   *   The chain tax rate resolver.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RounderInterface $rounder, ChainTaxRateResolverInterface $chain_rate_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $event_dispatcher);

    $this->rounder = $rounder;
    $this->chainRateResolver = $chain_rate_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_price.rounder'),
      $container->get('commerce_tax.chain_tax_rate_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRound() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    $store = $order->getStore();
    return $this->matchesAddress($store) || $this->matchesRegistrations($store);
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $store = $order->getStore();
    $prices_include_tax = $store->get('prices_include_tax')->value;
    $zones = $this->getZones();
    foreach ($order->getItems() as $order_item) {
      $customer_profile = $this->resolveCustomerProfile($order_item);
      if (!$customer_profile) {
        continue;
      }

      $rates = $this->resolveRates($order_item, $customer_profile);
      foreach ($rates as $zone_id => $rate) {
        $zone = $zones[$zone_id];
        $percentage = $rate->getPercentage();
        // Stores are allowed to enter prices without tax even if they're
        // going to be displayed with tax, and vice-versa.
        // Now that the rates are known, use them to determine the final
        // unit price (which will in turn finalize the order item total).
        if ($prices_include_tax != $this->isDisplayInclusive()) {
          $unit_price = $order_item->getUnitPrice();
          $tax_amount = $percentage->calculateTaxAmount($unit_price, $prices_include_tax);
          $tax_amount = $this->rounder->round($tax_amount);
          if ($prices_include_tax && !$this->isDisplayInclusive()) {
            $unit_price = $unit_price->subtract($tax_amount);
          }
          elseif (!$prices_include_tax && $this->isDisplayInclusive()) {
            $unit_price = $unit_price->add($tax_amount);
          }
          $order_item->setUnitPrice($unit_price);
        }
        // Now determine the tax amount, taking into account other adjustments.
        $adjusted_total_price = $order_item->getAdjustedTotalPrice(['promotion', 'fee']);
        $tax_amount = $percentage->calculateTaxAmount($adjusted_total_price, $this->isDisplayInclusive());
        if ($this->shouldRound()) {
          $tax_amount = $this->rounder->round($tax_amount);
        }

        $order_item->addAdjustment(new Adjustment([
          'type' => 'tax',
          'label' => $zone->getDisplayLabel(),
          'amount' => $tax_amount,
          'percentage' => $percentage->getNumber(),
          'source_id' => $this->entityId . '|' . $zone->getId() . '|' . $rate->getId(),
          'included' => $this->isDisplayInclusive(),
        ]));
      }
    }
  }

  /**
   * Checks whether the tax type matches the store's billing address.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return bool
   *   TRUE if the tax type matches the billing address, FALSE otherwise.
   */
  protected function matchesAddress(StoreInterface $store) {
    foreach ($this->getZones() as $zone) {
      if ($zone->match($store->getAddress())) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks whether the tax type matches the store's tax registrations.
   *
   * Countries have a yearly transaction threshold (such as $30k) which
   * when breached requires companies to register for tax collection.
   * This also often applies to foreign companies selling to that
   * country's residents. Furthermore, many countries are now trying to
   * make foreign companies collect their tax when selling digital products
   * to their residents, regardless of any threshold.
   * The $store->tax_registrations field allows merchants to precisely specify
   * for which countries they are collecting tax.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return bool
   *   TRUE if the tax type matches the tax registrations, FALSE otherwise.
   */
  protected function matchesRegistrations(StoreInterface $store) {
    foreach ($this->getZones() as $zone) {
      if ($this->checkRegistrations($store, $zone)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks whether the store is registered to collect taxes in the given zone.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\commerce_tax\TaxZone $zone
   *   The tax zone.
   *
   * @return bool
   *   TRUE if the store is registered in the given zone, FALSE otherwise.
   */
  protected function checkRegistrations(StoreInterface $store, TaxZone $zone) {
    foreach ($store->get('tax_registrations') as $field_item) {
      if ($zone->match(new Address($field_item->value))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Resolves the tax rates for the given order item and customer profile.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\profile\Entity\ProfileInterface $customer_profile
   *   The customer profile. Contains the address and tax number.
   *
   * @return \Drupal\commerce_tax\TaxRate[]
   *   The tax rates, keyed by tax zone ID.
   */
  protected function resolveRates(OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $rates = [];
    $zones = $this->resolveZones($order_item, $customer_profile);
    foreach ($zones as $zone) {
      $rate = $this->chainRateResolver->resolve($zone, $order_item, $customer_profile);
      if (is_object($rate)) {
        $rates[$zone->getId()] = $rate;
      }
    }
    return $rates;
  }

  /**
   * Resolves the tax zones for the given order item and customer profile.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\profile\Entity\ProfileInterface $customer_profile
   *   The customer profile. Contains the address and tax number.
   *
   * @return \Drupal\commerce_tax\TaxZone[]
   *   The tax zones.
   */
  protected function resolveZones(OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $customer_address = $customer_profile->get('address')->first();
    $resolved_zones = [];
    foreach ($this->getZones() as $zone) {
      if ($zone->match($customer_address)) {
        $resolved_zones[] = $zone;
      }
    }
    return $resolved_zones;
  }

  /**
   * Builds the summary of all available tax rates.
   *
   * @return array
   *   The summary form element.
   */
  protected function buildRateSummary() {
    $zones = $this->getZones();
    usort($zones, function ($a, $b) {
      /** @var \Drupal\commerce_tax\TaxZone $a */
      /** @var \Drupal\commerce_tax\TaxZone $b */
      return strcmp($a->getLabel(), $b->getLabel());
    });

    $element = [
      '#type' => 'details',
      '#title' => $this->t('Tax rates'),
      '#markup' => $this->t('The following tax rates are provided:'),
      '#open' => TRUE,
    ];
    $element['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Tax rate'),
        ['data' => $this->t('Percentage'), 'colspan' => 2],
      ],
      '#input' => FALSE,
    ];
    foreach ($zones as $zone) {
      if (count($zones) > 1) {
        $element['table']['zone-' . $zone->getId()] = [
          '#attributes' => [
            'class' => ['region-title'],
            'no_striping' => TRUE,
          ],
          'label' => [
            '#markup' => $zone->getLabel(),
            '#wrapper_attributes' => ['colspan' => 3],
          ],
        ];
      }
      foreach ($zone->getRates() as $rate) {
        $formatted_percentages = array_map(function ($percentage) {
          /** @var \Drupal\commerce_tax\TaxRatePercentage $percentage */
          return $percentage->toString();
        }, $rate->getPercentages());

        $element['table'][] = [
          'rate' => [
            '#markup' => $rate->getLabel(),
          ],
          'percentages' => [
            '#markup' => implode('<br>', $formatted_percentages),
          ],
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getZones() {
    if (empty($this->zones)) {
      $this->zones = $this->buildZones();
    }

    return $this->zones;
  }

  /**
   * Builds the tax zones.
   *
   * @return \Drupal\commerce_tax\TaxZone[]
   *   The tax zones.
   */
  abstract protected function buildZones();

}
