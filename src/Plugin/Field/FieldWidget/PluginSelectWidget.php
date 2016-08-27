<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

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
    return [
      '#type' => 'commerce_plugin_select',
      '#plugin_type' => $derivative,
      '#categories' => $this->fieldDefinition->getSetting('categories'),
      '#default_value' => [
        'target_plugin_id' => $items[$delta]->target_plugin_id,
        'target_plugin_configuration' => $items[$delta]->target_plugin_configuration,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // Iterate through the provided values and run the plugin configuration form
    // through the plugin's submit configuration form method, if available.
    foreach ($values as $delta => &$item_value) {
      if ($item_value['target_plugin_id'] == '_none') {
        continue;
      }

      $element = NestedArray::getValue($form, $item_value['array_parents']);

      /** @var \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.' . $item_value['target_plugin_type']);
      $plugin = $plugin_manager->createInstance($item_value['target_plugin_id'], $item_value['target_plugin_configuration']);

      // If the plugin implements the PluginFormInterface, pass the values to
      // its submit method for final processing.
      if ($plugin instanceof PluginFormInterface) {

        /** @var \Drupal\Component\Plugin\ConfigurablePluginInterface $plugin */
        $plugin->submitConfigurationForm($element['target_plugin_configuration'], $form_state);
        $item_value['target_plugin_configuration'] = $plugin->getConfiguration();
      }
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

}
