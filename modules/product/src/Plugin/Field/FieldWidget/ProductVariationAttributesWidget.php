<?php

namespace Drupal\commerce_product\Plugin\Field\FieldWidget;

use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
use Drupal\commerce_product\ProductVariationAttributeMapperInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_product_variation_attributes' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_variation_attributes",
 *   label = @Translation("Product variation attributes"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ProductVariationAttributesWidget extends ProductVariationWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The product attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The product variation attribute mapper.
   *
   * @var \Drupal\commerce_product\ProductVariationAttributeMapperInterface
   */
  protected $variationAttributeMapper;

  /**
   * Constructs a new ProductVariationAttributesWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The product attribute field manager.
   * @param \Drupal\commerce_product\ProductVariationAttributeMapperInterface $variation_attribute_mapper
   *   The product variation attribute mapper.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, ProductAttributeFieldManagerInterface $attribute_field_manager, ProductVariationAttributeMapperInterface $variation_attribute_mapper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_manager, $entity_repository);

    $this->attributeFieldManager = $attribute_field_manager;
    $this->variationAttributeMapper = $variation_attribute_mapper;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('commerce_product.attribute_field_manager'),
      $container->get('commerce_product.variation_attribute_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    $variations = $this->loadEnabledVariations($product);
    if (count($variations) === 0) {
      // Nothing to purchase, tell the parent form to hide itself.
      $form_state->set('hide_form', TRUE);
      $element['variation'] = [
        '#type' => 'value',
        '#value' => 0,
      ];
      return $element;
    }
    elseif (count($variations) === 1) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation */
      $selected_variation = reset($variations);
      // If there is 1 variation but there are attribute fields, then the
      // customer should still see the attribute widgets, to know what they're
      // buying (e.g a product only available in the Small size).
      if (empty($this->attributeFieldManager->getFieldDefinitions($selected_variation->bundle()))) {
        $element['variation'] = [
          '#type' => 'value',
          '#value' => $selected_variation->id(),
        ];
        return $element;
      }
    }

    // Build the full attribute form.
    $wrapper_id = Html::getUniqueId('commerce-product-add-to-cart-form');
    $form += [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    // If an operation caused the form to rebuild, select the variation from
    // the user's current input.
    $selected_variation = NULL;
    if ($form_state->isRebuilding()) {
      $parents = array_merge($element['#field_parents'], [$items->getName(), $delta, 'attributes']);
      $attribute_values = (array) NestedArray::getValue($form_state->getUserInput(), $parents);
      $selected_variation = $this->variationAttributeMapper->selectVariation($variations, $attribute_values);
    }
    // Otherwise fallback to the default.
    if (!$selected_variation) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $items->getEntity();
      if ($order_item->isNew()) {
        $selected_variation = $this->getDefaultVariation($product, $variations);
      }
      else {
        $selected_variation = $order_item->getPurchasedEntity();
      }
    }

    $element['variation'] = [
      '#type' => 'value',
      '#value' => $selected_variation->id(),
    ];
    // Set the selected variation in the form state for our AJAX callback.
    $form_state->set('selected_variation', $selected_variation->id());

    $element['attributes'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['attribute-widgets'],
      ],
    ];
    foreach ($this->variationAttributeMapper->prepareAttributes($selected_variation, $variations) as $field_name => $attribute) {
      $attribute_element = [
        '#type' => $attribute->getElementType(),
        '#title' => $attribute->getLabel(),
        '#options' => $attribute->getValues(),
        '#required' => $attribute->isRequired(),
        '#default_value' => $selected_variation->getAttributeValueId($field_name),
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $form['#wrapper_id'],
          // Prevent a jump to the top of the page.
          'disable-refocus' => TRUE,
        ],
      ];
      // Convert the _none option into #empty_value.
      if (isset($attribute_element['#options']['_none'])) {
        if (!$attribute_element['#required']) {
          $attribute_element['#empty_value'] = '';
        }
        unset($attribute_element['#options']['_none']);
      }
      // Optimize the UX of optional attributes:
      // - Hide attributes that have no values.
      // - Require attributes that have a value on each variation.
      if (empty($attribute_element['#options'])) {
        $attribute_element['#access'] = FALSE;
      }
      if (!isset($element['attributes'][$field_name]['#empty_value'])) {
        $attribute_element['#required'] = TRUE;
      }

      $element['attributes'][$field_name] = $attribute_element;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    $default_variation = $product->getDefaultVariation();
    $variations = $this->variationStorage->loadEnabled($product);

    foreach ($values as &$value) {
      $attribute_values = isset($value['attributes']) ? $value['attributes'] : [];
      $selected_variation = $this->variationAttributeMapper->selectVariation($variations, $attribute_values);
      if ($selected_variation) {
        $value['variation'] = $selected_variation->id();
      }
      else {
        $value['variation'] = $default_variation->id();
      }
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

}
