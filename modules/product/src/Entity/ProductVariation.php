<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce\EntityHelper;
use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Defines the product variation entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_product_variation",
 *   label = @Translation("Product variation"),
 *   label_collection = @Translation("Product variations"),
 *   label_singular = @Translation("product variation"),
 *   label_plural = @Translation("product variations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product variation",
 *     plural = "@count product variations",
 *   ),
 *   bundle_label = @Translation("Product variation type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_product\Event\ProductVariationEvent",
 *     "storage" = "Drupal\commerce_product\ProductVariationStorage",
 *     "access" = "Drupal\commerce_product\ProductVariationAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce_product\ProductVariationPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_product\ProductVariationListBuilder",
 *     "views_data" = "Drupal\commerce_product\ProductVariationViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\ProductVariationForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductVariationForm",
 *       "duplicate" = "Drupal\commerce_product\Form\ProductVariationForm",
 *       "delete" = "Drupal\commerce_product\Form\ProductVariationDeleteForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_product\ProductVariationRouteProvider",
 *     },
 *     "inline_form" = "Drupal\commerce_product\Form\ProductVariationInlineForm",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_product",
 *   translatable = TRUE,
 *   translation = {
 *     "content_translation" = {
 *       "access_callback" = "content_translation_translate_access"
 *     },
 *   },
 *   base_table = "commerce_product_variation",
 *   data_table = "commerce_product_variation_field_data",
 *   entity_keys = {
 *     "id" = "variation_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "published" = "status",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/product/{commerce_product}/variations/add",
 *     "edit-form" = "/product/{commerce_product}/variations/{commerce_product_variation}/edit",
 *     "duplicate-form" = "/product/{commerce_product}/variations/{commerce_product_variation}/duplicate",
 *     "delete-form" = "/product/{commerce_product}/variations/{commerce_product_variation}/delete",
 *     "collection" = "/product/{commerce_product}/variations",
 *     "drupal:content-translation-overview" = "/product/{commerce_product}/variations/{commerce_product_variation}/translations",
 *     "drupal:content-translation-add" = "/product/{commerce_product}/variations/{commerce_product_variation}/translations/add/{source}/{target}",
 *     "drupal:content-translation-edit" = "/product/{commerce_product}/variations/{commerce_product_variation}/translations/edit/{language}",
 *     "drupal:content-translation-delete" = "/product/{commerce_product}/variations/{commerce_product_variation}/translations/delete/{language}",
 *   },
 *   bundle_entity_type = "commerce_product_variation_type",
 *   field_ui_base_route = "entity.commerce_product_variation_type.edit_form",
 * )
 */
class ProductVariation extends CommerceContentEntityBase implements ProductVariationInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_product'] = $this->getProductId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // Product variation URLs depend on the parent product.
    if (!$this->getProductId()) {
      // RouteNotFoundException tells EntityBase::uriRelationships()
      // to skip this product variation's link relationships.
      throw new RouteNotFoundException();
    }

    // StringFormatter assumes 'revision' is always a valid link template.
    if (in_array($rel, ['canonical', 'revision'])) {
      $route_name = 'entity.commerce_product.canonical';
      $route_parameters = [
        'commerce_product' => $this->getProductId(),
      ];
      $options += [
        'query' => [
          'v' => $this->id(),
        ],
        'entity_type' => 'commerce_product',
        'entity' => $this->getProduct(),
        // Display links by default based on the current language.
        'language' => $this->language(),
      ];
      return new Url($route_name, $route_parameters, $options);
    }
    else {
      return parent::toUrl($rel, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProduct() {
    return $this->getTranslatedReferencedEntity('product_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getProductId() {
    return $this->get('product_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSku() {
    return $this->get('sku')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSku($sku) {
    $this->set('sku', $sku);
    return $this;
  }

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
  public function getListPrice() {
    if (!$this->get('list_price')->isEmpty()) {
      return $this->get('list_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setListPrice(Price $list_price) {
    return $this->set('list_price', $list_price);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice(Price $price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('published');
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', (bool) $active);
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
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('owner');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    $product = $this->getProduct();
    return $product ? $product->getStores() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    // The order item type is a bundle-level setting.
    $type_storage = $this->entityTypeManager()->getStorage('commerce_product_variation_type');
    $type_entity = $type_storage->load($this->bundle());

    return $type_entity->getOrderItemTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    $label = $this->label();
    if (!$label) {
      $label = $this->generateTitle();
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeFieldNames() {
    $attribute_field_manager = \Drupal::service('commerce_product.attribute_field_manager');
    $field_map = $attribute_field_manager->getFieldMap($this->bundle());
    return array_column($field_map, 'field_name');
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValueIds() {
    $attribute_ids = [];
    foreach ($this->getAttributeFieldNames() as $field_name) {
      $field = $this->get($field_name);
      if (!$field->isEmpty()) {
        $attribute_ids[$field_name] = $field->target_id;
      }
    }

    return $attribute_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValueId($field_name) {
    $attribute_field_names = $this->getAttributeFieldNames();
    if (!in_array($field_name, $attribute_field_names)) {
      throw new \InvalidArgumentException(sprintf('Unknown attribute field name "%s".', $field_name));
    }
    $attribute_id = NULL;
    $field = $this->get($field_name);
    if (!$field->isEmpty()) {
      $attribute_id = $field->target_id;
    }

    return $attribute_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValues() {
    $attribute_values = [];
    foreach ($this->getAttributeFieldNames() as $field_name) {
      $field = $this->get($field_name);
      if (!$field->isEmpty() && $field->entity) {
        $attribute_values[$field_name] = $field->entity;
      }
    }

    return $this->ensureTranslations($attribute_values);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValue($field_name) {
    $attribute_field_names = $this->getAttributeFieldNames();
    if (!in_array($field_name, $attribute_field_names)) {
      throw new \InvalidArgumentException(sprintf('Unknown attribute field name "%s".', $field_name));
    }
    $attribute_value = $this->getTranslatedReferencedEntity($field_name);
    return $attribute_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['store']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    // Invalidate the variations view builder and product caches.
    return Cache::mergeTags($tags, [
      'commerce_product:' . $this->getProductId(),
      'commerce_product_variation_view',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = $this->entityTypeManager()
      ->getStorage('commerce_product_variation_type')
      ->load($this->bundle());

    if ($variation_type->shouldGenerateTitle()) {
      $title = $this->generateTitle();
      $this->setTitle($title);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a reference on the parent product.
    $product = $this->getProduct();
    if ($product && !$product->hasVariation($this)) {
      $product->addVariation($this);
      $product->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $entities */
    foreach ($entities as $variation) {
      // Remove the reference from the parent product.
      $product = $variation->getProduct();
      if ($product && $product->hasVariation($variation)) {
        $product->removeVariation($variation);
        $product->save();
      }
    }
  }

  /**
   * Generates the variation title based on attribute values.
   *
   * @return string
   *   The generated value.
   */
  protected function generateTitle() {
    if (!$this->getProductId()) {
      // Title generation is not possible before the parent product is known.
      return '';
    }

    $product_title = $this->getProduct()->getTitle();
    if ($attribute_values = $this->getAttributeValues()) {
      $attribute_labels = EntityHelper::extractLabels($attribute_values);
      $title = $product_title . ' - ' . implode(', ', $attribute_labels);
    }
    else {
      // When there are no attribute fields, there's only one variation.
      $title = $product_title;
    }

    return $title;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The variation author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_product\Entity\ProductVariation::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // The product backreference, populated by Product::postSave().
    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setDescription(t('The parent product.'))
      ->setSetting('target_type', 'commerce_product')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setDescription(t('The unique, machine-readable identifier for a variation.'))
      ->setRequired(TRUE)
      ->addConstraint('ProductVariationSku')
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The variation title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['list_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('List price'))
      ->setDescription(t('The list price.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_list_price',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The price'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

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
      ->setDescription(t('The time when the variation was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the variation was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = [];
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($bundle);
    // $variation_type could be NULL if the method is invoked during uninstall.
    if ($variation_type && $variation_type->shouldGenerateTitle()) {
      // The title is always generated, the field needs to be hidden.
      // The widget is hidden in commerce_product_field_widget_form_alter()
      // since setDisplayOptions() can't affect existing form displays.
      $fields['title'] = clone $base_field_definitions['title'];
      $fields['title']->setRequired(FALSE);
      $fields['title']->setDisplayConfigurable('form', FALSE);
    }

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
