<?php

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;

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
class LineItemTable extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_name' => 'commerce_line_item_table',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $views = View::loadMultiple();
    $options = [];
    foreach ($views as $key => $view) {
      /** @var \Drupal\views\Entity\View $view */
      if ($view->get('base_table') == 'commerce_line_item') {
        $options[$key] = $view->label();
      }
    }

    $elements['view_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Line item table view'),
      '#description' => $this->t('Specify the line item table view to use in the order view.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('view_name'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view = View::load($this->getSetting('view_name'));
    $summary[] = t('View: @name', ['@name' => $view->label()]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $order = $items->getEntity();

    $elements = [
      '#type' => 'view',
      '#name' => $this->getSetting('view_name'),
      '#arguments' => [$order->id()],
      '#embed' => TRUE,
      '#cache' => [
        'tags' => [
          'config:' . $this->fieldDefinition->getTargetEntityTypeId() . '.' . $this->fieldDefinition->getTargetBundle() . '.' . $this->viewMode,
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();

    return $entity_type == 'commerce_order' && $field_name == 'line_items';
  }

}
