<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_plugin_select' widget.
 *
 * @FieldWidget(
 *   id = "commerce_plugin_select",
 *   label = @Translation("Plugin select"),
 *   field_types = {
 *     "commerce_plugin_item"
 *   },
 * )
 */
class PluginSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    list($field_type, $derivative) = explode(':', $this->fieldDefinition->getType());

    $element = [
      '#type' => 'container',
    ] + $element;
    $element['plugin_select'] = [
      '#type' => 'commerce_plugin_select',
      '#plugin_type' => $derivative,
      '#categories' => $this->fieldDefinition->getSetting('categories'),
      '#default_value' => [
        'target_plugin_id' => $items[$delta]->target_plugin_id,
        'target_plugin_configuration' => $items[$delta]->target_plugin_configuration ?: [],
      ],
      '#title' => $this->fieldDefinition->getLabel(),
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Remove the `plugin_select` container element key from values.
    foreach ($values as $delta => $value) {
      $values[$delta] = $value['plugin_select'];
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
