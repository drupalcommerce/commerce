<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\Entity\CommerceLineItemType.
 */

namespace Drupal\commerce_line_item\Entity;

use Drupal\commerce_line_item\CommerceLineItemTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Line item type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_line_item_type",
 *   label = @Translation("Line item type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_line_item\Form\CommerceLineItemTypeForm",
 *       "edit" = "Drupal\commerce_line_item\Form\CommerceLineItemTypeForm",
 *       "delete" = "Drupal\commerce_line_item\Form\CommerceLineItemTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_line_item\CommerceLineItemTypeListBuilder",
 *   },
 *   admin_permission = "administer line item types",
 *   config_prefix = "commerce_line_item_type",
 *   bundle_of = "commerce_line_item",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "entity.commerce_line_item_type.edit_form",
 *     "delete-form" = "entity.commerce_line_item_type.delete_form"
 *   }
 * )
 */
class CommerceLineItemType extends ConfigEntityBundleBase implements CommerceLineItemTypeInterface {

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
