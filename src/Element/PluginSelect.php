<?php

namespace Drupal\commerce\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element\FormElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Element for selecting plugins, and configuring them.
 *
 * @FormElement("commerce_plugin_select")
 *
 * Properties:
 * - #providers: Modules to restrict options to.
 *
 * Usage example:
 * @code
 * $form['plugin_item'] = [
 *   '#type' => 'commerce_plugin_select',
 *   '#title' => $this->t('Condition plugin'),
 *   '#categories' => ['user', 'system'],
 * ];
 * @endcode
 */
class PluginSelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#plugin_type' => NULL,
      '#categories' => [],
      '#title' => $this->t('Select plugin'),
      '#process' => [
        [$class, 'processPluginSelect'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validatePlugin'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Process callback.
   */
  public static function processPluginSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!$element['#plugin_type']) {
      throw new \InvalidArgumentException('You must specify the plugin type ID.');
    }

    $element['#tree'] = TRUE;

    /** @var \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);

    $values = $element['#value'];

    $target_plugin_id = !empty($values['target_plugin_id']) ? $values['target_plugin_id'] : '_none';

    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');
    $ajax_settings = [
      'callback' => [get_called_class(), 'pluginFormAjax'],
      'wrapper' => $ajax_wrapper_id,
    ];

    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Store #array_parents in the form state, so we can get the elements from
    // the complete form array by using only thes form state.
    $element['array_parents'] = [
      '#type' => 'value',
      '#value' => $element['#array_parents'],
    ];

    $element['target_plugin_type'] = [
      '#type' => 'value',
      '#value' => $element['#plugin_type'],
    ];

    $element['target_plugin_id'] = [
      '#type' => 'select',
      '#title' => $element['#title'],
      '#multiple' => FALSE,
      '#options' => [
        '_none' => t('None'),
      ],
      '#ajax' => $ajax_settings,
      '#default_value' => $target_plugin_id,
      '#ajax_array_parents' => $element['#array_parents'],
    ];

    $categories = array_combine($element['#categories'], $element['#categories']);
    $has_categories = !empty($categories);
    foreach ($plugin_manager->getDefinitions() as $definition) {
      // If categories have been specified, limit definitions based on them.
      if ($has_categories && !isset($categories[$definition['category']])) {
        continue;
      }

      // Group categorized plugins.
      if (isset($definition['category'])) {
        $element['target_plugin_id']['#options'][(string) $definition['category']][$definition['id']] = $definition['label'];
      }
      else {
        $element['target_plugin_id']['#options'][$definition['id']] = $definition['label'];
      }
    }

    if ($target_plugin_id != '_none') {
      /** @var \Drupal\Core\Executable\ExecutableInterface $plugin */
      $plugin = $plugin_manager->createInstance($target_plugin_id, $values['target_plugin_configuration']);
      if ($plugin instanceof  PluginFormInterface) {
        $element['target_plugin_configuration'] = [
          '#tree' => TRUE,
        ];
        $element['target_plugin_configuration'] = $plugin->buildConfigurationForm($element['target_plugin_configuration'], $form_state);
      }
    }

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function pluginFormAjax(&$form, FormStateInterface &$form_state, Request $request) {
    // Retrieve the element to be rendered.
    $triggering_element = $form_state->getTriggeringElement();
    $form_element = NestedArray::getValue($form, $triggering_element['#ajax_array_parents']);
    return $form_element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) {
      $input = $element['#default_value'];
    }
    return $input + [
      'target_plugin_id' => NULL,
      'target_plugin_configuration' => [],
    ];
  }

  /**
   * Validates the plugin's configuration.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validatePlugin(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($element['#parents']);

    $target_plugin_id = $values['target_plugin_id'];

    // If a plugin was selected, create an instance and pass the configuration
    // values to its configuration form validation method.
    if ($target_plugin_id != '_none') {
      $plugin_manager = \Drupal::service('plugin.manager.' . $values['target_plugin_type']);
      $plugin = $plugin_manager->createInstance($target_plugin_id, $values['target_plugin_configuration']);
      if ($plugin instanceof  PluginFormInterface) {
        $plugin->validateConfigurationForm($element, $form_state);
      }
    }
  }

}
