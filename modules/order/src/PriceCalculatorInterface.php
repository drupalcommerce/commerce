<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Calculates the price a purchasable entity would have if it was in an order.
 *
 * Used for product listings and pages, where an order doesn't exist yet.
 * When an order does exist, the actual calculation happens in OrderRefresh.
 *
 * @see \Drupal\commerce_order\OrderRefresh
 */
interface PriceCalculatorInterface {

  /**
   * Adds an order processor for the given adjustment type.
   *
   * @param \Drupal\commerce_order\OrderProcessorInterface $processor
   *   The order processor.
   * @param string $adjustment_type
   *   The adjustment type.
   */
  public function addProcessor(OrderProcessorInterface $processor, $adjustment_type);

  /**
   * Gets all order processors for the given adjustment type.
   *
   * @param string $adjustment_type
   *   The adjustment type.
   *
   * @return \Drupal\commerce_order\OrderProcessorInterface[]
   *   The order processors.
   */
  public function getProcessors($adjustment_type);

  /**
   * Calculates a purchasable entity's price.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   * @param string[] $adjustment_types
   *   The adjustment types to include in the calculated price.
   *   Examples: fee, promotion, tax.
   *
   * @return \Drupal\commerce_order\PriceCalculatorResult
   *   The result.
   */
  public function calculate(PurchasableEntityInterface $purchasable_entity, $quantity, Context $context, array $adjustment_types = []);

}
