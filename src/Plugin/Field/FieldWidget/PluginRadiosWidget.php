<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_plugin_radios' widget.
 *
 * @FieldWidget(
 *   id = "commerce_plugin_radios",
 *   label = @Translation("Plugin radios"),
 *   field_types = {
 *     "commerce_plugin_item"
 *   },
 *  )
 */
class PluginRadiosWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    list($field_type, $plugin_type) = explode(':', $this->fieldDefinition->getType());
    return [
      '#type' => 'commerce_plugin_select',
      '#plugin_element_type' => 'radios',
      '#plugin_type' => $plugin_type,
      '#categories' => $this->fieldDefinition->getSetting('categories'),
      '#default_value' => [
        'target_plugin_id' => $items[$delta]->target_plugin_id,
        'target_plugin_configuration' => $items[$delta]->target_plugin_configuration,
      ],
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->fieldDefinition->getLabel(),
    ];
  }

}
