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
 *   label_collection = @Translation("Product attributes"),
 *   label_singular = @Translation("product attribute"),
 *   label_plural = @Translation("product attributes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product attribute",
 *     plural = "@count product attributes",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "list_builder" = "Drupal\commerce_product\ProductAttributeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductAttributeForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductAttributeForm",
 *       "delete" = "Drupal\commerce_product\Form\ProductAttributeDeleteForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_attribute",
 *   admin_permission = "administer commerce_product_attribute",
 *   bundle_of = "commerce_product_attribute_value",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "elementType"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/product-attributes/add",
 *     "edit-form" = "/admin/commerce/product-attributes/manage/{commerce_product_attribute}",
 *     "delete-form" = "/admin/commerce/product-attributes/manage/{commerce_product_attribute}/delete",
 *     "collection" =  "/admin/commerce/product-attributes",
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
   * The attribute element type.
   *
   * @var string
   */
  protected $elementType = 'select';

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    /** @var \Drupal\commerce_product\ProductAttributeValueStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('commerce_product_attribute_value');
    $values = $storage->loadMultipleByAttribute($this->id());
    // Make sure that the values are returned in the attribute language.
    $langcode = $this->language()->getId();
    foreach ($values as $index => $value) {
      if ($value->hasTranslation($langcode)) {
        $values[$index] = $value->getTranslation($langcode);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementType() {
    return $this->elementType;
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
