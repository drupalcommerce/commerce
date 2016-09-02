<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\EntityAdjustableInterface;

/**
 * @todo Docblock
 */
interface PromotionApplicationServiceInterface {

  /**
   * Applies promotions to an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function apply(OrderInterface $order);

}
