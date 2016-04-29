<?php

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the product attribute entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_attribute",
 *   label = @Translation("Product attribute"),
 *   label_singular = @Translation("Product attribute"),
 *   label_plural = @Translation("Product attributes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product attribute",
 *     plural = "@count product attributes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_product\ProductAttributeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductAttributeForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductAttributeForm",
 *       "delete" = "Drupal\commerce_product\Form\ProductAttributeDeleteForm",
 *       "reset" = "Drupal\commerce_product\Form\ProductAttributeResetForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_attribute",
 *   admin_permission = "administer product attributes",
 *   bundle_of = "commerce_product_attribute_value",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/product-attributes/add",
 *     "edit-form" = "/admin/commerce/product-attributes/manage/{commerce_product_attribute}",
 *     "delete-form" = "/admin/commerce/product-attributes/manage/{commerce_product_attribute}/delete",
 *     "overview-form" = "/admin/commerce/product-attributes/manage/{commerce_product_attribute}/overview",
 *     "collection" =  "/admin/commerce/product-attributes",
 *     "reset-form" = "/admin/commerce/product-attributes/manage/{commerce_product_attribute}/reset",
 *   }
 * )
 */
class ProductAttribute extends ConfigEntityBundleBase implements ProductAttributeInterface {

  /**
   * The attribute ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The attribute label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    $storage = $this->entityTypeManager()->getStorage('commerce_product_attribute_value');
    return $storage->loadByProperties(['attribute' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface[] $entities */
    parent::postDelete($storage, $entities);

    // Delete all associated values.
    $values = [];
    foreach ($entities as $entity) {
      foreach ($entity->getValues() as $value) {
        $values[$value->id()] = $value;
      }
    }
    /** @var \Drupal\Core\Entity\EntityStorageInterface $value_storage */
    $value_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product_attribute_value');
    $value_storage->delete($values);
  }

}
