<?php
/**
 * @file
 * Contains \Drupal\commerce_order\LineItemMatcherInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\LineItemInterface;

/**
 * Match an existing line item for the PurchasableEntity.
 */
interface LineItemMatcherInterface {

  /**
   * Return the first matching line item.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The PurchasableEntity to Match.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Order to find a LineItem in.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   */
  public function match(PurchasableEntityInterface $entity, OrderInterface $order);

  /**
   * Return an array of matching line items.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The PurchasableEntity to Match.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Order to find a LineItem in.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface[]
   */
  public function matchAll(PurchasableEntityInterface $entity, OrderInterface $order);
}
