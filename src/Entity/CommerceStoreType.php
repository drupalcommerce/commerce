<?php

/**
 * @file
 * Contains Drupal\commerce\Entity\CommerceStoreType.
 */

namespace Drupal\commerce\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\commerce\CommerceStoreTypeInterface;

/**
 * Defines the Commerce Store Type entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_store_type",
 *   label = @Translation("Store type"),
 *   controllers = {
 *     "list_builder" = "Drupal\commerce\CommerceStoreTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\Form\CommerceStoreTypeForm",
 *       "edit" = "Drupal\commerce\Form\CommerceStoreTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceStoreTypeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer commerce_store_type entities",
 *   config_prefix = "commerce_store_type",
 *   bundle_of = "commerce_store",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "commerce.store_type_edit",
 *     "delete-form" = "commerce.store_type_delete"
 *   }
 * )
 */
class CommerceStoreType extends ConfigEntityBase implements CommerceStoreTypeInterface {
  /**
   * The store type machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The store type UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The store type label.
   *
   * @var string
   */
  public $label;
}
