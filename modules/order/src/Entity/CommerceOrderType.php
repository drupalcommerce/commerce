<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\CommerceOrderType.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_order\CommerceOrderTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Order type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_order_type",
 *   label = @Translation("Order type"),
 *   controllers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\CommerceOrderTypeForm",
 *       "edit" = "Drupal\commerce_order\Form\CommerceOrderTypeForm",
 *       "delete" = "Drupal\commerce_order\Form\CommerceOrderTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_order\Controller\CommerceOrderTypeListBuilder",
 *   },
 *   admin_permission = "administer commerce_order_type entities",
 *   config_prefix = "commerce_order_type",
 *   bundle_of = "commerce_order",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "entity.commerce_order.admin_form",
 *     "delete-form" = "entity.commerce_order_type.delete_form"
 *   }
 * )
 */
class CommerceOrderType extends ConfigEntityBundleBase implements CommerceOrderTypeInterface {

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
