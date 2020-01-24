<?php

namespace Drupal\commerce_tax\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Returns the tax zone's default tax rate.
 */
class DefaultTaxRateResolver implements TaxRateResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    return $zone->getDefaultRate();
  }

}
