<?php

namespace Drupal\commerce_tax\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the interface for tax rate resolvers.
 */
interface TaxRateResolverInterface {

  // Stops resolving when there is no applicable tax rate (cause the
  // provided order item is exempt from sales tax, for example).
  const NO_APPLICABLE_TAX_RATE = 'no_applicable_tax_rate';

  /**
   * Resolves the tax rate for the given tax zone.
   *
   * @param \Drupal\commerce_tax\TaxZone $zone
   *   The tax zone.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\profile\Entity\ProfileInterface $customer_profile
   *   The customer profile. Contains the address and tax number.
   *
   * @return \Drupal\commerce_tax\TaxRate|string|null
   *   The tax rate, NO_APPLICABLE_TAX_RATE, or NULL.
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile);

}
