<?php

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for product types.
 */
interface ProductTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the product type's matching variation type ID.
   *
   * @return string
   *   The variation type ID.
   */
  public function getVariationTypeId();

  /**
   * Sets the product type's matching variation type ID.
   *
   * @param string $variation_type_id
   *   The variation type ID.
   *
   * @return $this
   */
  public function setVariationTypeId($variation_type_id);

}
