<?php

namespace Drupal\commerce\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Element for selecting plugins, and configuring them.
 *
 * Usage example:
 * @code
 * $form['plugin_item'] = [
 *   '#type' => 'commerce_plugin_select',
 *   '#title' => $this->t('Condition plugin'),
 * ];
 * @endcode
 *
 * @FormElement("commerce_plugin_select")
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
      '#plugin_element_type' => 'select',
      '#title' => $this->t('Select plugin'),
      '#process' => [
        [$class, 'processPluginSelect'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Process callback.
   */
  public static function processPluginSelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!$element['#plugin_type']) {
      throw new \InvalidArgumentException('You must specify the plugin type ID.');
    }
    if (!in_array($element['#plugin_element_type'], ['radios', 'select'])) {
      throw new \InvalidArgumentException('The commerce_plugin_select element only supports select/radios.');
    }

    $values = $element['#value'];
    /** @var \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
    $plugins = array_map(function ($definition) {
      return $definition['label'];
    }, $plugin_manager->getDefinitions());
    $target_plugin_id = $values['target_plugin_id'];
    // The element is required, default to the first plugin.
    if ($element['#required'] && !$target_plugin_id) {
      $plugin_ids = array_keys($plugins);
      $target_plugin_id = reset($plugin_ids);
    }

    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');
    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element['#tree'] = TRUE;

    $element['target_plugin_id'] = [
      '#type' => $element['#plugin_element_type'],
      '#title' => $element['#title'],
      '#options' => $plugins,
      '#multiple' => FALSE,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#default_value' => $target_plugin_id,
      '#required' => $element['#required'],
    ];
    if (!$element['#required']) {
      $element['target_plugin_id']['#empty_value'] = '';
    }

    $element['target_plugin_configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => $element['#plugin_type'],
      '#plugin_id' => $target_plugin_id,
      '#default_value' => $values['target_plugin_configuration'],
    ];

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
      $input['target_plugin_id'] = '';
    }
    if (empty($input['target_plugin_configuration'])) {
      $input['target_plugin_configuration'] = [];
    }

    return $input;
  }

}
