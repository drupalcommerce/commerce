<?php

namespace Drupal\commerce_product\Plugin\Field\FieldFormatter;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_product\Entity\ProductAttributeInterface;
use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_product_attributes_overview' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_product_attributes_overview",
 *   label = @Translation("Product attributes overview"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class ProductAttributesOverview extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity|EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ProductAttributesOverview object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity|EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The attribute field manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, ProductAttributeFieldManagerInterface $attribute_field_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->attributeFieldManager = $attribute_field_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('commerce_product.attribute_field_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'attributes' => [],
      'view_mode' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    $attribute_storage = $this->entityTypeManager->getStorage('commerce_product_attribute');
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_bundle */
    $product_bundle = $product_type_storage->load($this->fieldDefinition->getTargetBundle());
    $attribute_map = $this->attributeFieldManager->getFieldMap($product_bundle->getVariationTypeId());
    $used_attributes = [];
    foreach (array_column($attribute_map, 'attribute_id') as $item) {
      $attribute = $attribute_storage->load($item);
      $used_attributes[$attribute->id()] = $attribute->label();
    }
    $view_modes = $this->entityDisplayRepository->getViewModes('commerce_product_attribute_value');
    $view_mode_labels = array_map(function ($view_mode) {
      return $view_mode['label'];
    }, $view_modes);

    $form['attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display the following attributes'),
      '#default_value' => $this->getSetting('attributes'),
      '#options' => $used_attributes,
    ];
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Attribute value display mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => ['default' => $this->t('Default')] + $view_mode_labels,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $attribute_storage = $this->entityTypeManager->getStorage('commerce_product_attribute');
    $attributes = [];
    if (empty($this->getSetting('attributes'))) {
      $attributes[] = $this->t('None');
    }
    else {
      $attributes = $attribute_storage->loadMultiple(array_filter($this->getSetting('attributes')));
      $attributes = EntityHelper::extractLabels($attributes);
    }
    $summary[] = $this->t('Displaying the following attributes: @attributes', [
      '@attributes' => implode(', ', $attributes),
    ]);
    $summary[] = $this->t('Attribute value display mode: @mode', [
      '@mode' => $this->getSetting('view_mode'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $attribute_storage = $this->entityTypeManager->getStorage('commerce_product_attribute');
    $elements = [];
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface[] $attributes */
    $attributes = $attribute_storage->loadMultiple(array_filter($this->getSetting('attributes')));
    foreach ($attributes as $attribute) {
      $elements[] = $this->getAttributeItemList($items, $attribute);
    }
    return $elements;
  }

  /**
   * Gets the renderable item list of attributes.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $variation_items
   *   The item list of variation entities.
   * @param \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute
   *   The product attribute.
   *
   * @return array
   *   The render array.
   */
  protected function getAttributeItemList(FieldItemListInterface $variation_items, ProductAttributeInterface $attribute) {
    $build = [
      '#theme' => 'item_list',
      '#title' => $attribute->label(),
      '#items' => [],
    ];

    $view_builder = $this->entityTypeManager->getViewBuilder('commerce_product_attribute_value');
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $variation */
    foreach ($variation_items as $variation) {
      /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute_value */
      $attribute_value = $variation->entity->getAttributeValue('attribute_' . $attribute->id());
      // If this attribute value has already been added, skip.
      if (isset($build['#items'][$attribute_value->id()])) {
        continue;
      }

      $attribute_render_array = $view_builder->view($attribute_value, $this->getSetting('view_mode'));
      $attribute_build = $this->renderer->render($attribute_render_array);
      $attribute_build = Link::fromTextAndUrl($attribute_build, $variation_items->getEntity()->toUrl())->toRenderable();
      $build['#items'][$attribute_value->id()] = $attribute_build;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product' && $field_name == 'variations';
  }

}
