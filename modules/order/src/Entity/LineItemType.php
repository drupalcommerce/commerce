<?php

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the line item type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_line_item_type",
 *   label = @Translation("Line item type"),
 *   label_singular = @Translation("Line item type"),
 *   label_plural = @Translation("Line item types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count line item type",
 *     plural = "@count line item types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\LineItemTypeForm",
 *       "edit" = "Drupal\commerce_order\Form\LineItemTypeForm",
 *       "delete" = "Drupal\commerce_order\Form\LineItemTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_order\LineItemTypeListBuilder",
 *   },
 *   admin_permission = "administer commerce line item types",
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
   * The purchasable entity type ID.
   *
   * @var string
   */
  protected $purchasableEntityType;

  /**
   * The order type ID.
   *
   * @var string
   */
  protected $orderType;

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityTypeId() {
    return $this->purchasableEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasableEntityTypeId($purchasable_entity_type_id) {
    $this->purchasableEntityType = $purchasable_entity_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderTypeId() {
    return $this->orderType;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderTypeId($order_type_id) {
    $this->orderType = $order_type_id;
    return $this;
  }

}
