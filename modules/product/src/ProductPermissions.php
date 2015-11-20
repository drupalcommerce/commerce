<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductPermissions.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_product\Entity\ProductType;

/**
 * Provides dynamic permissions for products of different types.
 */
class ProductPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of product type permissions.
   *
   * @return array
   *   The product type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function productTypePermissions() {
    $perms = array();
    // Generate product permissions for all product types.
    foreach (ProductType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of product permissions for a given product type.
   *
   * @param \Drupal\commerce_product\Entity\ProductType $type
   *   The product type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ProductType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "create $type_id product" => array(
        'title' => $this->t('%type_name: Create new product', $type_params),
      ),
      "edit own $type_id product" => array(
        'title' => $this->t('%type_name: Edit own product', $type_params),
      ),
      "edit any $type_id product" => array(
        'title' => $this->t('%type_name: Edit any product', $type_params),
      ),
      "delete own $type_id product" => array(
        'title' => $this->t('%type_name: Delete own product', $type_params),
      ),
      "delete any $type_id product" => array(
        'title' => $this->t('%type_name: Delete any product', $type_params),
      ),
    );
  }

}
