<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductTypeInterface.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for product types.
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
   * Sets the product type description.
   *
   * @param string $description
   *   The product description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the product type's matching variation type.
   *
   * @return string
   *   The variation type.
   */
  public function getVariationType();

  /**
   * Sets the product type's matching variation type.
   *
   * @param string $variationType
   *   The variation type.
   *
   * @return $this
   */
  public function setVariationType($variationType);

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
