<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\commerce\ConditionManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_conditions' widget.
 *
 * @FieldWidget(
 *   id = "commerce_conditions",
 *   label = @Translation("Conditions"),
 *   field_types = {
 *     "commerce_plugin_item:commerce_condition"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ConditionsWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The condition manager.
   *
   * @var \Drupal\commerce\ConditionManagerInterface
   */
  protected $conditionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ConditionsWidget object.
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
   * @param \Drupal\commerce\ConditionManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConditionManagerInterface $condition_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->conditionManager = $condition_manager;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('plugin.manager.commerce_condition'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'entity_types' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    // Remove entity types for which there are no conditions.
    $condition_entity_types = array_column($this->conditionManager->getDefinitions(), 'entity_type', 'entity_type');
    $entity_types = array_filter($entity_types, function ($entity_type) use ($condition_entity_types) {
      /** @var \Drupal\Core\Entity\EntityType $entity_type */
      return in_array($entity_type->id(), $condition_entity_types);
    });
    $entity_types = array_map(function ($entity_type) {
      /** @var \Drupal\Core\Entity\EntityType $entity_type */
      return $entity_type->getLabel();
    }, $entity_types);

    $element = [];
    $element['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types'),
      '#options' => $entity_types,
      '#default_value' => $this->getSetting('entity_types'),
      '#description' => $this->t('Only conditions matching the specified entity types will be displayed.'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $selected_entity_types = array_filter($this->getSetting('entity_types'));
    if (!empty($selected_entity_types)) {
      $entity_types = $this->entityTypeManager->getDefinitions();
      $entity_types = array_filter($entity_types, function ($entity_type) use ($selected_entity_types) {
        /** @var \Drupal\Core\Entity\EntityType $entity_type */
        return in_array($entity_type->id(), $selected_entity_types);
      });
      $entity_types = array_map(function ($entity_type) {
        /** @var \Drupal\Core\Entity\EntityType $entity_type */
        return $entity_type->getLabel();
      }, $entity_types);

      $summary[] = $this->t('Entity types: @entity_types', ['@entity_types' => implode(', ', $entity_types)]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $values = [];
    foreach ($items->getValue() as $value) {
      $values[] = [
        'plugin' => $value['target_plugin_id'],
        'configuration' => $value['target_plugin_configuration'],
      ];
    }

    $element['form'] = [
      '#type' => 'commerce_conditions',
      '#title' => $this->fieldDefinition->getLabel(),
      '#default_value' => $values,
      '#parent_entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
      '#entity_types' => array_filter($this->getSetting('entity_types')),
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values['form'] as $value) {
      if (!isset($value['plugin'])) {
        // This method is invoked during validation with incomplete values.
        // The commerce_conditions form element can't set the right values until form submit.
        continue;
      }

      $new_values[] = [
        'target_plugin_id' => $value['plugin'],
        'target_plugin_configuration' => $value['configuration'],
      ];
    }

    return $new_values;
  }

}
