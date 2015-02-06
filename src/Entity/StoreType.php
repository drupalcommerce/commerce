<?php

/**
 * @file
 * Contains Drupal\commerce\Entity\StoreType.
 */

namespace Drupal\commerce\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\commerce\StoreTypeInterface;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Defines the Commerce Store Type entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_store_type",
 *   label = @Translation("Store type"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce\StoreTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\Form\StoreTypeForm",
 *       "edit" = "Drupal\commerce\Form\StoreTypeForm",
 *       "delete" = "Drupal\commerce\Form\StoreTypeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer store types",
 *   config_prefix = "commerce_store_type",
 *   bundle_of = "commerce_store",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/store/types/{commerce_store_type}/edit",
 *     "delete-form" = "/admin/commerce/config/store/types/{commerce_store_type}/delete"
 *   }
 * )
 */
class StoreType extends ConfigEntityBundleBase implements StoreTypeInterface {

  /**
   * The store type machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The store type UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The store type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this store type.
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

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->access('delete')) {
      throw new EntityStorageException(strtr("Store Type %type may not be deleted.", array(
        '%type' => String::checkPlain($this->entityTypeId),
      )));
    }
    parent::delete();
  }

}
