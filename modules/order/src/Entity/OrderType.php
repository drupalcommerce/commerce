<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\OrderType.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Order type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_order_type",
 *   label = @Translation("Order type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\OrderTypeForm",
 *       "edit" = "Drupal\commerce_order\Form\OrderTypeForm",
 *       "delete" = "Drupal\commerce_order\Form\OrderTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_order\OrderTypeListBuilder",
 *   },
 *   admin_permission = "administer order types",
 *   config_prefix = "commerce_order_type",
 *   bundle_of = "commerce_order",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/order-types/{commerce_order_type}/edit",
 *     "delete-form" = "/admin/commerce/config/order-types/{commerce_order_type}/delete",
 *     "collection" = "/admin/commerce/config/order-types"
 *   }
 * )
 */
class OrderType extends ConfigEntityBundleBase implements OrderTypeInterface {

  /**
   * The order type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The order type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this order type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
