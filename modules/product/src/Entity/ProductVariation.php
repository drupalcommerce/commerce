<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\ProductVariation.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity\EntityKeysFieldsTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\user\UserInterface;

/**
 * Defines the product variation entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_product_variation",
 *   label = @Translation("Product variation"),
 *   handlers = {
 *     "event" = "Drupal\commerce_product\Event\ProductVariationEvent",
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "inline_form" = "Drupal\commerce_product\Form\ProductVariationInlineForm",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer products",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   base_table = "commerce_product_variation",
 *   data_table = "commerce_product_variation_field_data",
 *   entity_keys = {
 *     "id" = "variation_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "commerce_product_variation_type",
 *   field_ui_base_route = "entity.commerce_product_variation_type.edit_form",
 * )
 */
class ProductVariation extends ContentEntityBase implements ProductVariationInterface {

  use EntityChangedTrait, EntityKeysFieldsTrait;

  /**
   * Local cache for attribute field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $attributeFieldDefinitions;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // A label callback was registered to override the default logic.
    $callback = $this->getEntityType()->getLabelCallback();
    if ($callback && is_callable($callback)) {
      return call_user_func($callback, $this);
    }

    if ($attributes = $this->getAttributeFields()) {
      $attribute_labels = [];
      foreach ($attributes as $attribute) {
        if (!$attribute->isEmpty()) {
          $attribute_labels[] = $attribute->entity->label();
        }
      }

      $label = implode(', ', $attribute_labels);
    }
    else {
      // When there are no attribute fields, there's always only one variation.
      $label = t('Default');
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getProduct() {
    return $this->get('product_id')->entity;
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
  public function getPrice() {
    return $this->get('price')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('status');
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
    $this->get('uid')->target_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this->set('uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemType() {
    // The line item type is a bundle-level setting.
    $type_storage = $this->entityManager()->getStorage('commerce_product_variation_type');
    $type_entity = $type_storage->load($this->bundle());

    return $type_entity->getLineItemType();
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemTitle() {
    $product = $this->getProduct();
    if (!$product) {
      throw new EntityMalformedException(sprintf('Product variation #%d is missing the product backreference.', $this->id()));
    }

    $title = $product->getTitle();
    if ($this->getAttributeFieldDefinitions()) {
      $title .= ' - ' . $this->label();
    }

    return $title;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeFields() {
    $fields = [];
    foreach ($this->getAttributeFieldDefinitions() as $name => $definition) {
      $fields[$name] = $this->get($name);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeFieldDefinitions() {
    if (!isset($this->attributeFieldDefinitions)) {
      $definitions = $this->getFieldDefinitions();
      $this->attributeFieldDefinitions = array_filter($definitions, function ($definition) {
        if ($definition instanceof FieldConfigInterface) {
          return $definition->getThirdPartySetting('commerce_product', 'attribute_field');
        }
      });
    }

    return $this->attributeFieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = self::entityKeysBaseFieldDefinitions($entity_type);

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
      ->setReadOnly(TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setDescription(t('The unique, machine-readable identifier for a variation.'))
      ->setRequired(TRUE)
      ->addConstraint('ProductVariationSku')
      ->setTranslatable(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The price is not required because it's not guaranteed to be used
    // for storage (there might be a price per currency, role, country, etc).
    $fields['price'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Price'))
      ->setDescription(t('The variation price'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether the variation is active.'))
      ->setDefaultValue(TRUE)
      ->setTranslatable(TRUE)
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
