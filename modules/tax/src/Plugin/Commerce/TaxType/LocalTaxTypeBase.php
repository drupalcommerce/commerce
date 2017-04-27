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
   * @var \Drupal\commerce_tax\ChainTaxRateResolverInterface
   */
  protected $chainRateResolver;

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
   * @param \Drupal\commerce_tax\ChainTaxRateResolverInterface $chain_rate_resolver
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
  public function applies(OrderInterface $order) {
    // The store must belong to one of the zones,
    // or be registered to collect taxes there.
    $store = $order->getStore();
    foreach ($this->getZones() as $zone) {
      if ($zone->match($store->getAddress())) {
        return TRUE;
      }
      elseif ($this->checkStoreRegistration($store, $zone)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $prices_include_tax = $order->getStore()->get('prices_include_tax')->value;
    foreach ($order->getItems() as $order_item) {
      $customer_profile = $this->resolveCustomerProfile($order_item);
      if (!$customer_profile) {
        continue;
      }

      $zones = $this->resolveZones($order_item, $customer_profile);
      foreach ($zones as $zone) {
        $rate = $this->chainRateResolver->resolve($zone, $order_item, $customer_profile);
        if (!is_object($rate)) {
          // No applicable rate found.
          continue;
        }
        $unit_price = $order_item->getUnitPrice();
        $rate_amount = $rate->getAmount()->getAmount();
        $adjustment_amount = $unit_price->multiply($rate_amount);
        if ($prices_include_tax) {
          $divisor = (string) (1 + $rate_amount);
          $adjustment_amount = $adjustment_amount->divide($divisor);
        }
        if ($this->shouldRound()) {
          $adjustment_amount = $this->rounder->round($adjustment_amount);
        }
        if ($prices_include_tax && !$this->isDisplayInclusive()) {
          $unit_price = $unit_price->subtract($adjustment_amount);
          $order_item->setUnitPrice($unit_price);
        }

        $order_item->addAdjustment(new Adjustment([
          'type' => 'tax',
          'label' => $this->getDisplayLabel(),
          'amount' => $adjustment_amount,
          'source_id' => $this->entityId . '|' . $zone->getId() . '|' . $rate->getId(),
          'included' => $this->isDisplayInclusive(),
        ]));
      }
    }
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
    $customer_address = $customer_profile->address->first();
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
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Tax rates'),
      '#markup' => $this->t('The following tax rates are provided:'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    $element['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Tax rate'),
        ['data' => $this->t('Amount'), 'colspan' => 2],
      ],
      '#input' => FALSE,
    ];
    foreach ($this->getZones() as $tax_zone) {
      $element['table']['zone-' . $tax_zone->getId()] = [
        '#attributes' => [
          'class' => ['region-title'],
          'no_striping' => TRUE,
        ],
        'label' => [
          '#markup' => $tax_zone->getLabel(),
          '#wrapper_attributes' => ['colspan' => 3],
        ],
      ];
      foreach ($tax_zone->getRates() as $tax_rate) {
        $formatted_amounts = array_map(function ($amount) {
          /** @var \Drupal\commerce_tax\TaxRateAmount $amount */
          return $amount->toString();
        }, $tax_rate->getAmounts());

        $element['table'][] = [
          'tax_rate' => [
            '#markup' => $tax_rate->getLabel(),
          ],
          'amounts' => [
            '#markup' => implode('<br>', $formatted_amounts),
          ],
        ];
      }
    }

    return $element;
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
  protected function checkStoreRegistration(StoreInterface $store, TaxZone $zone) {
    foreach ($store->get('tax_registrations') as $field_item) {
      if ($zone->match(new Address($field_item->value))) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
