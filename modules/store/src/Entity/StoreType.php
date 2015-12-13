<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\StoreType.
 */

namespace Drupal\commerce_store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the store type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_store_type",
 *   label = @Translation("Store type"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_store\StoreTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_store\Form\StoreTypeForm",
 *       "edit" = "Drupal\commerce_store\Form\StoreTypeForm",
 *       "delete" = "Drupal\commerce_store\Form\StoreTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "create" = "Drupal\entity\Routing\CreateHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer store types",
 *   config_prefix = "commerce_store_type",
 *   bundle_of = "commerce_store",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "description",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/store-types/add",
 *     "edit-form" = "/admin/commerce/config/store-types/{commerce_store_type}/edit",
 *     "delete-form" = "/admin/commerce/config/store-types/{commerce_store_type}/delete",
 *     "collection" = "/admin/commerce/config/store-types",
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

}
