<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductVariationType.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the product variation type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_variation_type",
 *   label = @Translation("Product variation type"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_product\ProductVariationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductVariationTypeForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductVariationTypeForm",
 *       "delete" = "Drupal\commerce_product\Form\ProductVariationTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "create" = "Drupal\entity\Routing\CreateHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_variation_type",
 *   admin_permission = "administer product types",
 *   bundle_of = "commerce_product_variation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "lineItemType",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-variation-types/add",
 *     "edit-form" = "/admin/commerce/config/product-variation-types/{commerce_product_variation_type}/edit",
 *     "delete-form" = "/admin/commerce/config/product-variation-types/{commerce_product_variation_type}/delete",
 *     "collection" =  "/admin/commerce/config/product-variation-types"
 *   }
 * )
 */
class ProductVariationType extends ConfigEntityBundleBase implements ProductVariationTypeInterface {

  /**
   * The product variation type id.
   *
   * @var string
   */
  protected $id;

  /**
   * The line item type.
   *
   * @var string
   */
  protected $lineItemType;

  /**
   * {@inheritdoc}
   */
  public function getLineItemType() {
    return $this->lineItemType;
  }

  /**
   * {@inheritdoc}
   */
  public function setLineItemType($line_item_type) {
    $this->lineItemType = $line_item_type;
    return $this;
  }

}
