<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Defines the product variation entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_product_variation",
 *   label = @Translation("Product variation"),
 *   label_singular = @Translation("Product variation"),
 *   label_plural = @Translation("Product variations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product variation",
 *     plural = "@count product variations",
 *   ),
 *   bundle_label = @Translation("Product variation type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_product\Event\ProductVariationEvent",
 *     "storage" = "Drupal\commerce_product\ProductVariationStorage",
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
 *   content_translation_ui_skip = TRUE,
 *   base_table = "commerce_product_variation",
 *   data_table = "commerce_product_variation_field_data",
 *   entity_keys = {
 *     "id" = "variation_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "commerce_product_variation_type",
 *   field_ui_base_route = "entity.commerce_product_variation_type.edit_form",
 * )
 */
class ProductVariation extends ContentEntityBase implements ProductVariationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel == 'canonical') {
      $route_name = 'entity.commerce_product.canonical';
      $route_parameters = [
        'commerce_product' => $this->getProductId(),
      ];
      $options = [
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
    return $this->get('uid')->target_id;
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
  public function getLineItemTypeId() {
    // The line item type is a bundle-level setting.
    $type_storage = $this->entityTypeManager()->getStorage('commerce_product_variation_type');
    $type_entity = $type_storage->load($this->bundle());

    return $type_entity->getLineItemTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemTitle() {
    return $this->label();
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
      if (!$field->isEmpty()) {
        $attribute_values[$field_name] = $field->entity;
      }
    }

    return $attribute_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValue($field_name) {
    $attribute_field_names = $this->getAttributeFieldNames();
    if (!in_array($field_name, $attribute_field_names)) {
      throw new \InvalidArgumentException(sprintf('Unknown attribute field name "%s".', $field_name));
    }
    $attribute_value = NULL;
    $field = $this->get($field_name);
    if (!$field->isEmpty()) {
      $attribute_value = $field->entity;
    }

    return $attribute_value;
  }

  /**
   * Gets the names of the entity's attribute fields.
   *
   * @return string[]
   *   The attribute field names.
   */
  protected function getAttributeFieldNames() {
    $attribute_field_manager = \Drupal::service('commerce_product.attribute_field_manager');
    $field_map = $attribute_field_manager->getFieldMap($this->bundle());
    return array_column($field_map, 'field_name');
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
      $attribute_labels = array_map(function ($attribute_value) {
        return $attribute_value->label();
      }, $attribute_values);

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
      ->setDescription(t("The unique, machine-readable identifier for a variation. Default is the PHP's iniqid()."))
      ->setDefaultValueCallback('Drupal\commerce_product\Entity\ProductVariation::getUniqSku')
      ->setRequired(TRUE)
      ->addConstraint('ProductVariationSku')
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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The variation title.'))
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

    // The price is not required because it's not guaranteed to be used
    // for storage (there might be a price per currency, role, country, etc).
    $fields['price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The variation price'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether the variation is active.'))
      ->setDefaultValue(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 99,
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

  /**
   * Default value callback for 'sku' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return string
   *   A prefixed unique identifier based on the current time in microseconds.
   */
  public static function getUniqSku() {
    return \uniqid('sku-');
  }

  /**
   * An AJAX callback to create all possible variations for commerce_product.
   *
   * @see commerce_product_field_widget_form_alter()
   *
   * @param array $form
   *
   *   An array form for commerce_product.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   *   The form state of the commerce_product form with at least one variation
   *   created.
   */
  public static function createAllVariations(array $form, FormStateInterface $form_state) {
    $ief_id = $form['variations']['widget']['#ief_id'];
    if (!$all = static::getUsedAttributesCombinations($form_state, $ief_id)) {
      return;
    }
    $timestamp = time();
    $ief_entity = end($all['ief_entities']);
    foreach ($all['possible']['combinations'] as $combination) {
      if (!in_array($combination, $all['combinations'])) {
        $variation = $all['last_variation']->createDuplicate()
          ->set('variation_id', NULL)
          ->setSku(static::getUniqSku())
          ->setChangedTime($timestamp)
          ->setCreatedTime($timestamp);
        foreach ($combination as $field_name => $id) {
          $variation->get($field_name)->setValue(['target_id' => $id == '_none' ? NULL : $id]);
        }
        $variation->updateOriginalValues();
        $ief_entity['entity'] = $variation;
        $ief_entity['weight'] += 1;
        array_push($all['ief_entities'], $ief_entity);
        // To avoid the same CreatedTime on multiple variations increase the
        // $timestamp by one second instead of calling time() in the loop.
        $timestamp++;
      }
    }
    $form_state->set(['inline_entity_form', $ief_id, 'entities'], $all['ief_entities']);
    $form_state->setRebuild();
  }

  /**
   * Gets duplicated variations combinations and labels.
   *
   * @param string $ief_id
   *
   *   The id of inline_entity_form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   *   The form state of the commerce_product form with at least one variation
   *   created.
   *
   * @return array|null
   *   An array of used combinations, possible combinations and their quantity,
   *   last variation, inline_entity_form entities, duplicated combinations and
   *   an HTML list of duplicated variations labels if they are found.
   */
  public static function getDuplicatedAttributesCombinations(FormStateInterface $form_state, $ief_id = '') {
    if (!$all = static::getUsedAttributesCombinations($form_state, $ief_id)) {
      return;
    }
    $all['used'] = $all['duplications'] = [];
    foreach ($all['combinations'] as $combination) {
      if (in_array($combination, $all['used'])) {
        $all['duplications'][] = $combination;
      }
      else {
        $all['used'][] = $combination;
      }
    }
    if (!empty($all['duplications'])) {
      $field_options = $all['last_variation']->getAttributeFieldOptionIds();
      $all['duplications_list'] = '<ul>';
      foreach ($all['duplications'] as $fields) {
        $label = [];
        foreach ($fields as $field_name => $id) {
          if (isset($field_options['options'][$field_name][$id])) {
            $label[] = $field_options['options'][$field_name][$id];
          }
        }
        $label = Html::escape(implode(', ', $label));
        $all['duplications_list'] .= '<li>' . $label . '</li>';
      }
      $all['duplications_list'] .= '</ul>';
    }

    return $all;
  }

  /**
   * Gets all used variations attributes combinations on a commerce_product.
   *
   * @param string $ief_id
   *
   *   The id of inline_entity_form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   *   The form state of the commerce_product form with at least one variation
   *   created.
   *
   * @return array|null
   *   An array of used combinations, possible combinations and their quantity,
   *   last variation and inline_entity_form entities.
   */
  public static function getUsedAttributesCombinations(FormStateInterface $form_state, $ief_id = '') {
    $ief_entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']) ?: [];
    $ief_entity = end($ief_entities);
    if (!isset($ief_entity['entity'])) {
      return;
    }
    $all = [];
    $all['ief_entities'] = $ief_entities;
    $all['last_variation'] = $ief_entity['entity'];
    $all['possible'] = $all['last_variation']->getAttributesCombinations();
    $nones = array_fill_keys(array_keys($all['last_variation']->getAttributeFieldOptionIds()['ids']), '_none');
    foreach ($ief_entities as $variation) {
      $last = $variation['entity'];
      // getAttributeValueIds() does not return empty optional fields.
      // Merge 'field_name' => '_none' as a choice in the combination.
      // @todo Render '_none' option on an Add to Cart form.
      // @see ProductVariationAttributesWidget->formElement()
      // @see CommerceProductRenderedAttribute::processRadios()
      $all['combinations'][] = array_merge($nones, $last->getAttributeValueIds());
    }

    return $all;
  }

  /**
   * Gets the ids of the variation's attribute fields.
   *
   * @return array
   *   An array of ids arrays keyed by field name.
   */
  public function getAttributeFieldOptionIds() {
    $field_options = $ids = $fields = [];
    foreach ($this->getAttributeFieldNames() as $field_name) {
      $definition = $this->get($field_name)->getFieldDefinition();
      $fields[$field_name] = $definition->getFieldStorageDefinition()
        ->getOptionsProvider('target_id', $this)
        ->getSettableOptions(\Drupal::currentUser());
      $ids[$field_name] = array_keys($fields[$field_name]);
      // Optional fields need '_none' id as a possible choice.
      !$definition->isRequired() && array_unshift($ids[$field_name], '_none');
    }
    $field_options['ids'] = $ids;
    $field_options['options'] = $fields;

    return $field_options;
  }

  /**
   * Gets all ids combinations of the commerce_product's attribute fields.
   *
   * @return array
   *   An array of ids combinations and combinations quantity.
   */
  public function getAttributesCombinations() {
    $combinations = $this->getArrayValueCombinations($this->getAttributeFieldOptionIds()['ids']);
    $field_names = array_values($this->getAttributeFieldNames());
    $all = [];
    foreach ($combinations as $combination) {
      array_walk($combination, function (&$id) {
      $id = (string) $id;
      }
      );
      $all['combinations'][] = array_combine($field_names, $combination);
    }
    $all['count'] = count($combinations);

    return $all;
  }

  /**
   * Gets combinations of an Array values.
   *
   * See the function
   * @link https://gist.github.com/fabiocicerchia/4556892 source origin @endlink
   * .
   *
   * @param array $data
   *
   *   An array with mixed data.
   *
   * @return array
   *   An array of all possible array values combinations.
   */
  protected function getArrayValueCombinations(array $data = array(), array &$all = array(), array $group = array(), $value = NULL, $i = 0) {
    $keys = array_keys($data);
    if (isset($value) === TRUE) {
      array_push($group, $value);
    }
    if ($i >= count($data)) {
      array_push($all, $group);
    }
    elseif (isset($keys[$i])) {
      $currentKey = $keys[$i];
      $currentElement = $data[$currentKey];
      foreach ($currentElement as $key => $val) {
        $this->getArrayValueCombinations($data, $all, $group, $val, $i + 1);
      }
    }

    return $all;
  }

}
