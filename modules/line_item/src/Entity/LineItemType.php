<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\Entity\LineItemType.
 */

namespace Drupal\commerce_line_item\Entity;

use Drupal\commerce_line_item\LineItemTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Line item type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_line_item_type",
 *   label = @Translation("Line item type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_line_item\Form\LineItemTypeForm",
 *       "edit" = "Drupal\commerce_line_item\Form\LineItemTypeForm",
 *       "delete" = "Drupal\commerce_line_item\Form\LineItemTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_line_item\LineItemTypeListBuilder",
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
 *     "description",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/line-item-types/{commerce_line_item_type}/edit",
 *     "delete-form" = "/admin/commerce/config/line-item-types/{commerce_line_item_type}/edit",
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
   * A brief description of this line item type.
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
