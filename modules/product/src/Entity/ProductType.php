<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductType.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

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
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_type",
 *   admin_permission = "administer product types",
 *   bundle_of = "commerce_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "digital",
 *     "description",
 *     "variationType",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/product-types/{commerce_product_type}/edit",
 *     "delete-form" = "/admin/commerce/config/product-types/{commerce_product_type}/delete",
 *     "collection" = "/admin/commerce/config/product-types"
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
   * The matching variation type.
   *
   * @var string
   */
  protected $variationType;

  /**
   * Option to specify if the product type is a digital service.
   *
   * @var bool
   */
  protected $digital;

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
  public function getVariationType() {
    return $this->variationType;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariationType($variationType) {
    $this->variationType = $variationType;
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

}
