<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\LineItemType.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the line item type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_line_item_type",
 *   label = @Translation("Line item type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\LineItemTypeForm",
 *       "edit" = "Drupal\commerce_order\Form\LineItemTypeForm",
 *       "delete" = "Drupal\commerce_order\Form\LineItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "create" = "Drupal\entity\Routing\CreateHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_order\LineItemTypeListBuilder",
 *   },
 *   admin_permission = "administer line item types",
 *   config_prefix = "commerce_line_item_type",
 *   bundle_of = "commerce_line_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "purchasableEntityType",
 *     "orderType"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/line-item-types/add",
 *     "edit-form" = "/admin/commerce/config/line-item-types/{commerce_line_item_type}/edit",
 *     "delete-form" = "/admin/commerce/config/line-item-types/{commerce_line_item_type}/delete",
 *     "collection" = "/admin/commerce/config/line-item-types"
 *   }
 * )
 */
class LineItemType extends ConfigEntityBundleBase implements LineItemTypeInterface {

  /**
   * The line item type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The line item type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The purchasable entity type.
   *
   * @var string
   */
  protected $purchasableEntityType;

  /**
   * The order type.
   *
   * @var string
   */
  protected $orderType;

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityType() {
    return $this->purchasableEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasableEntityType($purchasable_entity_type) {
    $this->purchasableEntityType = $purchasable_entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderType() {
    return $this->orderType;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderType($order_type) {
    $this->orderType = $order_type;
    return $this;
  }

}
