<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\LineItemMatcherInterface.
 */

namespace Drupal\commerce_cart;

use Drupal\commerce_order\Entity\LineItemInterface;

/**
 * Finds matching line items.
 *
 * Used for combining line items in the add to cart process.
 */
interface LineItemMatcherInterface {

  /**
   * Finds the best matching line item for the given line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   * @param \Drupal\commerce_order\Entity\LineItemInterface[] $line_items
   *   The line items to match against.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface|null
   *   A matching line item, or NULL if none was found.
   */
  public function match(LineItemInterface $line_item, array $line_items);

  /**
   * Finds all matching line items for the given line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   * @param \Drupal\commerce_order\Entity\LineItemInterface[] $line_items
   *   The line items to match against.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface[]
   *   The matching line items.
   */
  public function matchAll(LineItemInterface $line_item, array $line_items);

}
