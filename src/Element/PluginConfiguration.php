<?php

namespace Drupal\commerce\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for configuring plugins.
 *
 * Usage example:
 * @code
 * $form['configuration'] = [
 *   '#type' => 'commerce_plugin_configuration',
 *   '#plugin_type' => 'commerce_promotion',
 *   '#plugin_id' => 'order_total_price',
 *   '#default_value' => [
 *     'operator' => '<',
 *     'amount' => [
 *       'number' => '10.00',
 *       'currency_code' => 'USD',
 *     ],
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("commerce_plugin_configuration")
 */
class PluginConfiguration extends FormElement {

  use CommerceElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#plugin_type' => NULL,
      '#plugin_id' => NULL,
      '#enforce_unique_parents' => TRUE,
      '#default_value' => [],

      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processPluginConfiguration'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
        [$class, 'validatePluginConfiguration'],
      ],
      '#commerce_element_submit' => [
        [$class, 'submitPluginConfiguration'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the plugin configuration form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @throws \InvalidArgumentException
   *   Thrown for missing #plugin_type or malformed #default_value properties.
   */
  public static function processPluginConfiguration(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#plugin_type'])) {
      throw new \InvalidArgumentException('The commerce_plugin_configuration #plugin_type property is required.');
    }
    if (!is_array($element['#default_value'])) {
      throw new \InvalidArgumentException('The commerce_plugin_configuration #default_value must be an array.');
    }
    if (empty($element['#plugin_id'])) {
      // A plugin hasn't been selected yet.
      return $element;
    }

    /** @var \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
    /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
    $plugin = $plugin_manager->createInstance($element['#plugin_id'], $element['#default_value']);
    $element['form'] = [];
    if (!empty($element['#enforce_unique_parents'])) {
      // NestedArray::setValue() crashes when switching between two plugins
      // that share a configuration element of the same name, but not the
      // same type (e.g. "amount" of type number/commerce_price).
      // Configuration must be keyed by plugin ID in $form_state to prevent
      // that, either on this level, or in a parent form element.
      $element['form']['#parents'] = array_merge($element['#parents'], [$element['#plugin_id']]);
    }
    $element['form'] = $plugin->buildConfigurationForm($element['form'], $form_state);

    return $element;
  }

  /**
   * Validates the plugin configuration.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validatePluginConfiguration(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#plugin_id'])) {
      /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
      $plugin = $plugin_manager->createInstance($element['#plugin_id'], $element['#default_value']);
      $plugin->validateConfigurationForm($element['form'], $form_state);
    }
  }

  /**
   * Submits the plugin configuration.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitPluginConfiguration(array &$element, FormStateInterface $form_state) {
    if (!empty($element['#plugin_id'])) {
      /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
      $plugin_manager = \Drupal::service('plugin.manager.' . $element['#plugin_type']);
      $plugin = $plugin_manager->createInstance($element['#plugin_id'], $element['#default_value']);
      $plugin->submitConfigurationForm($element['form'], $form_state);
      $form_state->setValueForElement($element, $plugin->getConfiguration());
    }
  }

}
