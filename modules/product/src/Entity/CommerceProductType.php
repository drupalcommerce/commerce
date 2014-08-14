<?php

/**
 * @file
 * Contains Drupal\commerce\Entity\CommerceProductType.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\CommerceConfigEntityBundleBase;
use Drupal\commerce_product\CommerceProductTypeInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Defines the Commerce Product Type entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_type",
 *   label = @Translation("Product type"),
 *   controllers = {
 *     "access" = "Drupal\commerce\CommerceConfigEntityAccessController",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\CommerceProductTypeForm",
 *       "edit" = "Drupal\commerce_product\Form\CommerceProductTypeForm",
 *       "delete" = "Drupal\commerce_product\Form\CommerceProductTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_product\CommerceProductTypeListBuilder"
 *   },
 *   config_prefix = "commerce_product_type",
 *   admin_permission = "administer commerce_product_type entities",
 *   bundle_of = "commerce_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "commerce_product.product_type_edit",
 *     "delete-form" = "commerce_product.product_type_delete"
 *   }
 * )
 */
class CommerceProductType extends CommerceConfigEntityBundleBase implements CommerceProductTypeInterface {

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
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->access('delete')) {
      throw new EntityStorageException(strtr("Product Type %type may not be deleted.", array(
        '%type' => String::checkPlain($this->entityTypeId),
      )));
    }
    parent::delete();
  }

  /**
   * The product type description.
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
  public function getProductCount() {
    return $this->getContentCount();
  }

}
