<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce\EntityOwnerTrait;
use Drupal\commerce_product\Event\ProductDefaultVariationEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the product entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_product",
 *   label = @Translation("Product"),
 *   label_collection = @Translation("Products"),
 *   label_singular = @Translation("product"),
 *   label_plural = @Translation("products"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product",
 *     plural = "@count products",
 *   ),
 *   bundle_label = @Translation("Product type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_product\Event\ProductEvent",
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "query_access" = "Drupal\entity\QueryAccess\QueryAccessHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "view_builder" = "Drupal\commerce_product\ProductViewBuilder",
 *     "list_builder" = "Drupal\commerce_product\ProductListBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_product\Form\ProductForm",
 *       "add" = "Drupal\commerce_product\Form\ProductForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "translation" = "Drupal\commerce_product\ProductTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_product",
 *   permission_granularity = "bundle",
 *   translatable = TRUE,
 *   base_table = "commerce_product",
 *   data_table = "commerce_product_field_data",
 *   entity_keys = {
 *     "id" = "product_id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/product/{commerce_product}",
 *     "add-page" = "/product/add",
 *     "add-form" = "/product/add/{commerce_product_type}",
 *     "edit-form" = "/product/{commerce_product}/edit",
 *     "delete-form" = "/product/{commerce_product}/delete",
 *     "delete-multiple-form" = "/admin/commerce/products/delete",
 *     "collection" = "/admin/commerce/products"
 *   },
 *   bundle_entity_type = "commerce_product_type",
 *   field_ui_base_route = "entity.commerce_product_type.edit_form",
 * )
 */
class Product extends CommerceContentEntityBase implements ProductInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use EntityPublishedTrait;

  /**
   * The default product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $defaultVariation;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return $this->getTranslatedReferencedEntities('stores');
  }

  /**
   * {@inheritdoc}
   */
  public function setStores(array $stores) {
    $this->set('stores', $stores);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $store_ids = [];
    foreach ($this->get('stores') as $store_item) {
      $store_ids[] = $store_item->target_id;
    }
    return $store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreIds(array $store_ids) {
    $this->set('stores', $store_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationIds() {
    $variation_ids = [];
    foreach ($this->get('variations') as $field_item) {
      $variation_ids[] = $field_item->target_id;
    }
    return $variation_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariations() {
    return $this->getTranslatedReferencedEntities('variations');
  }

  /**
   * {@inheritdoc}
   */
  public function setVariations(array $variations) {
    $this->set('variations', $variations);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariations() {
    return !$this->get('variations')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addVariation(ProductVariationInterface $variation) {
    if (!$this->hasVariation($variation)) {
      $this->get('variations')->appendItem($variation);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeVariation(ProductVariationInterface $variation) {
    $index = $this->getVariationIndex($variation);
    if ($index !== FALSE) {
      $this->get('variations')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariation(ProductVariationInterface $variation) {
    return in_array($variation->id(), $this->getVariationIds());
  }

  /**
   * Gets the index of the given variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The variation.
   *
   * @return int|bool
   *   The index of the given variation, or FALSE if not found.
   */
  protected function getVariationIndex(ProductVariationInterface $variation) {
    return array_search($variation->id(), $this->getVariationIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultVariation() {
    if ($this->defaultVariation === NULL) {
      $default_variation = NULL;
      foreach ($this->getVariations() as $variation) {
        // Return the first active variation.
        if ($variation->isPublished() && $variation->access('view')) {
          $default_variation = $variation;
          break;
        }
      }
      // Allow other modules to set the default variation.
      $event = new ProductDefaultVariationEvent($default_variation, $this);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(ProductEvents::PRODUCT_DEFAULT_VARIATION, $event);
      $this->defaultVariation = $event->getDefaultVariation();
    }
    return $this->defaultVariation;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // Explicitly set the owner ID to 0 if the translation owner is anonymous
      // (This will ensure we don't store a broken reference in case the user
      // no longer exists).
      if ($translation->getOwner()->isAnonymous()) {
        $translation->setOwnerId(0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each product variation.
    foreach ($this->variations as $item) {
      $variation = $item->entity;
      if ($variation->product_id->isEmpty()) {
        $variation->product_id = $this->id();
        $variation->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.query_args:v', 'store']);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the product variations of a deleted product.
    $variations = [];
    foreach ($entities as $entity) {
      if (empty($entity->variations)) {
        continue;
      }
      foreach ($entity->variations as $item) {
        $variations[$item->target_id] = $item->entity;
      }
    }
    $variation_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product_variation');
    $variation_storage->delete($variations);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Stores'))
      ->setDescription(t('The product stores.'))
      ->setRequired(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The product author.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The product title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variations'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Variations'))
      ->setDescription(t('The product variations.'))
      ->setRequired(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'type' => 'commerce_add_to_cart',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('URL alias'))
      ->setDescription(t('The product URL alias.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setComputed(TRUE);

    $fields['status']
      ->setLabel(t('Published'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the product was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the product was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = [];
    $fields['variations'] = clone $base_field_definitions['variations'];
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = ProductType::load($bundle);
    if ($product_type) {
      $variation_type_id = $product_type->getVariationTypeId();
      // Restrict the variations field to the configured variation type.
      $fields['variations']->setSetting('handler_settings', [
        'target_bundles' => [$variation_type_id => $variation_type_id],
      ]);
    }

    return $fields;
  }

}
