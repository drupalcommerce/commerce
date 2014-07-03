<?php

/**
 * @file
 * Contains Drupal\commerce\Entity\CommerceProductType.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\commerce_product\CommerceProductTypeInterface;

/**
 * Defines the Commerce Product Type entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_type",
 *   label = @Translation("Product type"),
 *   controllers = {
 *     "list_builder" = "Drupal\commerce_product\CommerceProductTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\CommerceProductTypeForm",
 *       "edit" = "Drupal\commerce_product\Form\CommerceProductTypeForm",
 *       "delete" = "Drupal\commerce_product\Form\CommerceProductTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "commerce_product_type",
 *   admin_permission = "administer commerce_product_type entities",
 *   bundle_of = "commerce_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "commerce_product.product_type_edit",
 *     "delete-form" = "commerce_product.product_type_delete"
 *   }
 * )
 */
class CommerceProductType extends ConfigEntityBundleBase implements CommerceProductTypeInterface {
  /**
   * The product type machine name and primary ID.
   *
   * @var string
   */
  public $id;

  /**
   * The product type UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The product type label.
   *
   * @var string
   */
  public $label;
}
