<?php

namespace Drupal\commerce_product\Plugin\Field\FieldWidget;

use Drupal\commerce\InlineFormManager;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_product_single_variation' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_single_variation",
 *   label = @Translation("Single variation (Product information)"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class SingleVariationWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new SingleVariationWidget object.
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
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->inlineFormManager = $inline_form_manager;
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
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // Do not allow this widget to be used as a default value widget.
    if ($this->isDefaultValueWidget($form_state)) {
      return $form;
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $variation = !$items->isEmpty() ? $items->first()->entity : NULL;
    if (!$variation) {
      $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->create([
        'type' => reset($this->getFieldSetting('handler_settings')['target_bundles']),
        'langcode' => $items->getEntity()->language()->getId(),
      ]);
    }
    $inline_form = $this->inlineFormManager->createInstance('content_entity', [], $variation);

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      // Remove the "required" cue, it's display-only and confusing.
      '#required' => FALSE,
      // Use a custom title for the widget because "Variations" doesn't make
      // sense in a single variation context.
      '#field_title' => $this->t('Product information'),
      '#after_build' => [
        [get_class($this), 'removeTranslatabilityClue'],
      ],
    ] + $element;

    $element['entity'] = [
      '#parents' => array_merge($element['#field_parents'], [$items->getName(), 'entity']),
      '#inline_form' => $inline_form,
    ];
    $element['entity'] = $inline_form->buildInlineForm($element['entity'], $form_state);

    return $element;
  }

  /**
   * After-build callback for removing the translatability clue from the widget.
   *
   * The variations field is not translatable, to avoid different translations
   * having different references. However, that causes ContentTranslationHandler
   * to add an "(all languages)" suffix to the widget title. That suffix is
   * incorrect, since the content_entity inline form does ensure that specific
   * entity translations are being edited.
   *
   * @see ContentTranslationHandler::addTranslatabilityClue()
   */
  public static function removeTranslatabilityClue(array $element, FormStateInterface $form_state) {
    $element['#title'] = $element['#field_title'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      $items->filterEmptyItems();
      return;
    }

    $parents = [$this->fieldDefinition->getName(), 'widget'];
    if ($form['#type'] != 'inline_entity_form') {
      $parents = array_merge($form['#parents'], $parents);
    }
    $element = NestedArray::getValue($form, $parents);
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $element['entity']['#inline_form'];
    $values = $items->getValue();
    $values[0] = ['entity' => $inline_form->getEntity()];
    $items->setValue($values);
    $items->filterEmptyItems();
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
