<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the order item type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_order_item_type",
 *   label = @Translation("Order item type"),
 *   label_collection = @Translation("Order item types"),
 *   label_singular = @Translation("order item type"),
 *   label_plural = @Translation("order item types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order item type",
 *     plural = "@count order item types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\OrderItemTypeForm",
 *       "edit" = "Drupal\commerce_order\Form\OrderItemTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_order\OrderItemTypeListBuilder",
 *   },
 *   admin_permission = "administer commerce_order_type",
 *   config_prefix = "commerce_order_item_type",
 *   bundle_of = "commerce_order_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "purchasableEntityType",
 *     "orderType",
 *     "traits",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/order-item-types/add",
 *     "edit-form" = "/admin/commerce/config/order-item-types/{commerce_order_item_type}/edit",
 *     "delete-form" = "/admin/commerce/config/order-item-types/{commerce_order_item_type}/delete",
 *     "collection" = "/admin/commerce/config/order-item-types"
 *   }
 * )
 */
class OrderItemType extends CommerceBundleEntityBase implements OrderItemTypeInterface {

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
