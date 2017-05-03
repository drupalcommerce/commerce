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

  use CommerceElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#plugin_type' => NULL,
      '#plugin_element_type' => 'select',
      '#categories' => [],
      '#title' => $this->t('Select plugin'),
      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processPluginSelect'],
        [$class, 'processAjaxForm'],
      ],
      '#after_build' => [
        [$class, 'clearValues'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
        [$class, 'validatePlugin'],
      ],
      '#commerce_element_submit' => [
        [$class, 'submitPlugin'],
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
    if (!in_array($element['#plugin_element_type'], ['radios', 'select'])) {
      throw new \InvalidArgumentException('The commerce_plugin_select element only supports select/radios.');
    }

    $values = $element['#value'];
    $target_plugin_id = $values['target_plugin_id'];

    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');
    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element['#tree'] = TRUE;

    $element['target_plugin_id'] = [
      '#type' => $element['#plugin_element_type'],
      '#title' => $element['#title'],
      '#multiple' => FALSE,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#default_value' => $target_plugin_id,
      '#required' => $element['#required'],
    ];
    // Add a "_none" option if the element is not required.
    if (!$element['#required']) {
      $element['target_plugin_id']['#options']['_none'] = t('None');
    }

    /** @var \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
    $categories = array_combine($element['#categories'], $element['#categories']);
    $has_categories = !empty($categories);
    $definitions = [];
    foreach ($plugin_manager->getDefinitions() as $definition) {
      // If categories have been specified, limit definitions based on them.
      if ($has_categories && !isset($categories[$definition['category']])) {
        continue;
      }

      // Group categorized plugins, and if using a select element.
      if (isset($definition['category']) && $element['#plugin_element_type'] == 'select') {
        $element['target_plugin_id']['#options'][(string) $definition['category']][$definition['id']] = $definition['label'];
      }
      else {
        $element['target_plugin_id']['#options'][$definition['id']] = $definition['label'];
      }
      $definitions[] = $definition['id'];
    }

    // If the element is required, set the default value to the first plugin.
    // definition available in the options array.
    if ($element['#required'] && $target_plugin_id == '_none' && !empty($element['target_plugin_id']['#options'])) {
      $target_plugin_id = reset($definitions);
      $element['target_plugin_id']['#default_value'] = $target_plugin_id;
    }

    $element['target_plugin_configuration'] = [
      '#type' => 'container',
    ];
    if (!empty($target_plugin_id) && $target_plugin_id != '_none') {
      /** @var \Drupal\Core\Executable\ExecutableInterface $plugin */
      $plugin = $plugin_manager->createInstance($target_plugin_id, $values['target_plugin_configuration']);
      if ($plugin instanceof PluginFormInterface) {
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
  public static function ajaxRefresh(&$form, FormStateInterface $form_state, Request $request) {
    $target_plugin_id_element = $form_state->getTriggeringElement();

    // Radios are an extra parent deep compared to the select.
    $slice_length = ($target_plugin_id_element['#type'] == 'radio') ? -2 : -1;

    $plugin_select_element = NestedArray::getValue($form, array_slice($target_plugin_id_element['#array_parents'], 0, $slice_length));
    return $plugin_select_element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) {
      $input = $element['#default_value'];
    }
    if (empty($input['target_plugin_id'])) {
      $input['target_plugin_id'] = '_none';
    }
    if (empty($input['target_plugin_configuration'])) {
      $input['target_plugin_configuration'] = [];
    }

    return $input;
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
      /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
      $plugin = $plugin_manager->createInstance($target_plugin_id, $element['#default_value']['target_plugin_configuration']);
      if ($plugin instanceof PluginFormInterface) {
        $plugin->validateConfigurationForm($element['target_plugin_configuration'], $form_state);
      }
    }
  }

  /**
   * Submits the plugin's configuration.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitPlugin(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $target_plugin_id = $values['target_plugin_id'];
    // If a plugin was selected, create an instance and pass the configuration
    // values to its configuration form submission method.
    if ($target_plugin_id != '_none') {
      /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
      $plugin = $plugin_manager->createInstance($target_plugin_id, $element['#default_value']['target_plugin_configuration']);
      if ($plugin instanceof PluginFormInterface) {
        /** @var \Drupal\Component\Plugin\ConfigurablePluginInterface $plugin */
        $plugin->submitConfigurationForm($element['target_plugin_configuration'], $form_state);
        $values['target_plugin_configuration'] = $plugin->getConfiguration();
        $form_state->setValueForElement($element, $values);
      }
    }
  }

  /**
   * Clears the plugin-specific form values when the target plugin changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    $triggering_element_name = end($triggering_element['#parents']);
    if ($triggering_element_name == 'target_plugin_id') {
      $input = &$form_state->getUserInput();
      $parents = array_merge($element['#parents'], ['target_plugin_configuration']);
      NestedArray::setValue($input, $parents, '');
      $element['target_plugin_configuration']['#value'] = '';
    }

    return $element;
  }

}
