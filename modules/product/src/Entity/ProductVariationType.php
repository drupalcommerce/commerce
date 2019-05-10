<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the product variation type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_variation_type",
 *   label = @Translation("Product variation type"),
 *   label_collection = @Translation("Product variation types"),
 *   label_singular = @Translation("product variation type"),
 *   label_plural = @Translation("product variation types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product variation type",
 *     plural = "@count product variation types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_product\ProductVariationTypeAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_product\ProductVariationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductVariationTypeForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductVariationTypeForm",
 *       "duplicate" = "Drupal\commerce_product\Form\ProductVariationTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_variation_type",
 *   admin_permission = "administer commerce_product_type",
 *   bundle_of = "commerce_product_variation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "orderItemType",
 *     "generateTitle",
 *     "traits",
 *     "locked",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-variation-types/add",
 *     "edit-form" = "/admin/commerce/config/product-variation-types/{commerce_product_variation_type}/edit",
 *     "duplicate-form" = "/admin/commerce/config/product-variation-types/{commerce_product_variation_type}/duplicate",
 *     "delete-form" = "/admin/commerce/config/product-variation-types/{commerce_product_variation_type}/delete",
 *     "collection" =  "/admin/commerce/config/product-variation-types"
 *   }
 * )
 */
class ProductVariationType extends CommerceBundleEntityBase implements ProductVariationTypeInterface {

  /**
   * The order item type ID.
   *
   * @var string
   */
  protected $orderItemType;

  /**
   * Whether the product variation title should be automatically generated.
   *
   * @var bool
   */
  protected $generateTitle;

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return $this->orderItemType;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItemTypeId($order_item_type_id) {
    $this->orderItemType = $order_item_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldGenerateTitle() {
    return (bool) $this->generateTitle;
  }

  /**
   * {@inheritdoc}
   */
  public function setGenerateTitle($generate_title) {
    $this->generateTitle = $generate_title;
    return $this;
  }

}
