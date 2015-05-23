<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductTypeInterface.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface ProductTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the product type description.
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
   * Gets the digital property of the product type.
   *
   * @return bool
   *   The digital property value.
   */
  public function isDigital();

  /**
   * Sets the digital property of the product type.
   *
   * @param bool
   *   The new value for the digital property.
   *
   * @return $this
   */
  public function setDigital($digital);

}
