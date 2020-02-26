<?php

namespace Drupal\commerce_tax_test\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\commerce_tax\Resolver\TaxRateResolverInterface;
use Drupal\commerce_tax\Resolver\TaxTypeAwareInterface;
use Drupal\commerce_tax\Resolver\TaxTypeAwareTrait;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;

class TaxRateResolver implements TaxRateResolverInterface, TaxTypeAwareInterface {

  use TaxTypeAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    // Confirm that a tax type is always set.
    assert($this->taxType instanceof TaxTypeInterface);

    // Use the "reduced" rate for order items with a quantity larger than 20.
    if (Calculator::compare($order_item->getQuantity(), '20') == 1) {
      return $zone->getRate('reduced') ?: $zone->getDefaultRate();
    }
  }

}
