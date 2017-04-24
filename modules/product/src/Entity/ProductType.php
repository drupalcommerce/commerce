<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the product type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_type",
 *   label = @Translation("Product type"),
 *   label_collection = @Translation("Product types"),
 *   label_singular = @Translation("product type"),
 *   label_plural = @Translation("product types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product type",
 *     plural = "@count product types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_product\ProductTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductTypeForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_type",
 *   admin_permission = "administer commerce_product_type",
 *   bundle_of = "commerce_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "variationType",
 *     "injectVariationFields",
 *     "traits",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-types/add",
 *     "edit-form" = "/admin/commerce/config/product-types/{commerce_product_type}/edit",
 *     "delete-form" = "/admin/commerce/config/product-types/{commerce_product_type}/delete",
 *     "collection" = "/admin/commerce/config/product-types"
 *   }
 * )
 */
class ProductType extends CommerceBundleEntityBase implements ProductTypeInterface {

  /**
   * The product type description.
   *
   * @var string
   */
  protected $description;

  /**
   * The variation type ID.
   *
   * @var string
   */
  protected $variationType;

  /**
   * Indicates if variation fields should be injected.
   *
   * @var bool
   */
  protected $injectVariationFields = TRUE;

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
  public function getVariationTypeId() {
    return $this->variationType;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariationTypeId($variation_type_id) {
    $this->variationType = $variation_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldInjectVariationFields() {
    return $this->injectVariationFields;
  }

  /**
   * {@inheritdoc}
   */
  public function setInjectVariationFields($inject) {
    $this->injectVariationFields = (bool) $inject;
    return $this;
  }

}
