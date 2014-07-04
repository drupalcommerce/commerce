<?php

/**
 * @file
 * Contains Drupal\commerce_product\CommerceProductTypeInterface.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface CommerceProductTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the number of product entities existing with this type.
   *
   * @return int
   */
  public function getProductCount();
}
