<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductType.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\commerce_product\ProductTypeInterface;

/**
 * Defines the Commerce Product Type entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_type",
 *   label = @Translation("Product type"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_product\ProductTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductTypeForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductTypeForm",
 *       "delete" = "Drupal\commerce_product\Form\ProductTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "commerce_product_type",
 *   admin_permission = "administer product types",
 *   bundle_of = "commerce_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/product-types/{commerce_product_type}/edit",
 *     "delete-form" = "/admin/commerce/config/product-types/{commerce_product_type}/delete"
 *   }
 * )
 */
class ProductType extends ConfigEntityBundleBase implements ProductTypeInterface {

  /**
   * The product type machine name and primary ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The product type UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The product type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The product type description.
   *
   * @var string
   */
  protected $description;

  /**
   * Option to specify if the product type is a digital service.
   *
   * @var bool
   */
  protected $digital;

  /**
   * The default revision setting for products of this type.
   *
   * @var bool
   */
  public $revision;

  /**
   * Indicates whether a body field should be created for this product type.
   *
   * This property affects entity creation only. It allows default configuration
   * of modules and installation profiles to specify whether a Body field should
   * be created for this bundle.
   *
   * @var bool
   */
  protected $createBody = TRUE;

  /**
   * The label to use for the body field upon entity creation.
   *
   * @var string
   */
  protected $createBodyLabel = 'Body';

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
  public function isDigital() {
    return $this->digital ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDigital($digital) {
    $this->digital = $digital;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Create a body if the create_body property is true and we're not in
    // the syncing process.
    if ($this->get('create_body') && !$this->isSyncing()) {
      $label = $this->get('create_body_label');
      commerce_product_add_body_field($this->id, $label);
    }
   }

}
