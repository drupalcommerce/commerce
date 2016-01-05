<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductTypeInterface.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\entity\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for product types.
 */
interface ProductTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

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
   * @param string $variation_type
   *   The variation type.
   *
   * @return $this
   */
  public function setVariationType($variation_type);

}
