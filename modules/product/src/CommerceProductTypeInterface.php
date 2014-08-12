<?php

/**
 * @file
 * Contains Drupal\commerce_product\CommerceProductTypeInterface.
 */

namespace Drupal\commerce_product;

use Drupal\commerce\CommerceEntityTypeInterface;

/**
 * Provides an interface defining a Product Type entity.
 */
interface CommerceProductTypeInterface extends CommerceEntityTypeInterface {

  /**
   * Returns the product type description.
   *
   * @return string
   *   The product type description.
   */
  public function getDescription();

  /**
   * Sets the description of the product type.
   *
   * @param string $description
   *   The new description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the number of product entities existing with this type.
   *
   * @return int
   */
  public function getProductCount();
}
