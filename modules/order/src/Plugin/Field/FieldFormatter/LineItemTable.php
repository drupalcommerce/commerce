<?php

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_line_item_table' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_line_item_table",
 *   label = @Translation("Line item table"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class LineItemTable extends FormatterBase implements FormatterInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * Constructs a new PriceDefaultFormatter object.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, QueryInterface $query_interface) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityQuery = $query_interface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $order = $items->getEntity();
    return [
      '#type' => 'view',
      '#name' => $this->getSetting('line_item_table_view'),
      '#arguments' => [$order->id()],
      '#embed' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order' && $field_name == 'line_items';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'line_item_table_view' => 'commerce_line_item_table',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $views = $this->entityQuery
      ->condition('base_table', 'commerce_line_item')
      ->execute();
    $elements['line_item_table_view'] = [
      '#type' => 'select',
      '#options' => $views,
      '#default_value' => $this->getSetting('line_item_table_view'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [t('View: @view', array('@view' => $this->getSetting('line_item_table_view')))];
  }
}
