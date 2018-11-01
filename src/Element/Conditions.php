<?php

namespace Drupal\commerce\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for configuring conditions.
 *
 * Usage example:
 * @code
 * $form['conditions'] = [
 *   '#type' => 'commerce_conditions',
 *   '#title' => 'Conditions',
 *   '#parent_entity_type' => 'commerce_promotion',
 *   '#entity_types' => ['commerce_order', 'commerce_order_item'],
 *   '#default_value' => [
 *     [
 *       'plugin' => 'order_total_price',
 *       'configuration' => [
 *         'operator' => '<',
 *         'amount' => [
 *           'number' => '10.00',
 *           'currency_code' => 'USD',
 *         ],
 *       ],
 *     ],
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("commerce_conditions")
 */
class Conditions extends FormElement {

  use CommerceElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#parent_entity_type' => NULL,
      '#entity_types' => [],
      '#default_value' => [],
      '#title' => '',

      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processConditions'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
      ],
      '#commerce_element_submit' => [
        [$class, 'submitConditions'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the conditions form element.
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
   *   Thrown for missing or malformed #parent_entity_type, #entity_types,
   *   #default_value properties.
   */
  public static function processConditions(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#parent_entity_type'])) {
      throw new \InvalidArgumentException('The commerce_conditions element requires the #parent_entity_type property.');
    }
    if (empty($element['#entity_types'])) {
      throw new \InvalidArgumentException('The commerce_conditions element requires the #entity_types property.');
    }
    if (!is_array($element['#entity_types'])) {
      throw new \InvalidArgumentException('The commerce_conditions #entity_types property must be an array.');
    }
    if (!is_array($element['#default_value'])) {
      throw new \InvalidArgumentException('The commerce_conditions #default_value property must be an array.');
    }

    $default_value = array_column($element['#default_value'], 'configuration', 'plugin');
    /** @var \Drupal\commerce\ConditionManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.commerce_condition');
    $definitions = $plugin_manager->getFilteredDefinitions($element['#parent_entity_type'], $element['#entity_types']);
    $grouped_definitions = [];
    foreach ($definitions as $plugin_id => $definition) {
      $category = (string) $definition['category'];
      $grouped_definitions[$category][$plugin_id] = $definition;
    }
    ksort($grouped_definitions);
    $tab_group = implode('][', array_merge($element['#parents'], ['conditions']));

    $element['#attached']['library'][] = 'commerce/conditions';
    $element['#categories'] = [];

    // Render vertical tabs only if there is more than a single category.
    $render_vertical_tabs = count($grouped_definitions) > 1;
    if ($render_vertical_tabs) {
      $element['conditions'] = [
        '#type' => 'vertical_tabs',
        '#title' => $element['#title'],
      ];
    }
    else {
      $element['conditions_title'] = [
        '#type' => 'item',
        '#title' => $element['#title'],
      ];
      $element['conditions'] = [
        '#type' => 'container',
      ];
    }

    foreach ($grouped_definitions as $category => $definitions) {
      $category_id = preg_replace('/[^a-zA-Z\-]/', '_', strtolower($category));
      $element['#categories'][] = $category_id;

      $element[$category_id] = [
        '#type' => $render_vertical_tabs ? 'details' : 'container',
        '#title' => $category,
        '#group' => $tab_group,
      ];
      foreach ($definitions as $plugin_id => $definition) {
        $category_parents = array_merge($element['#parents'], [$category_id]);
        $checkbox_parents = array_merge($category_parents, [$plugin_id, 'enable']);
        $checkbox_name = self::buildElementName($checkbox_parents);
        $checkbox_input = NestedArray::getValue($form_state->getUserInput(), $checkbox_parents);
        $enabled = isset($default_value[$plugin_id]) || !empty($checkbox_input);
        $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper-' . $plugin_id);
        // Preselect the first vertical tab that has an enabled condition.
        if ($enabled && !isset($element['conditions']['#default_tab'])) {
          $element['conditions']['#default_tab'] = 'edit-' . implode('-', $category_parents);
        }

        $element[$category_id][$plugin_id] = [
          '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
          '#suffix' => '</div>',
        ];
        $element[$category_id][$plugin_id]['enable'] = [
          '#type' => 'checkbox',
          '#title' => $definition['display_label'],
          '#default_value' => $enabled,
          '#attributes' => [
            'class' => ['enable'],
          ],
          '#ajax' => [
            'callback' => [get_called_class(), 'ajaxRefresh'],
            'wrapper' => $ajax_wrapper_id,
          ],
        ];
        if ($enabled) {
          $element[$category_id][$plugin_id]['configuration'] = [
            '#type' => 'commerce_plugin_configuration',
            '#plugin_type' => 'commerce_condition',
            '#plugin_id' => $plugin_id,
            '#default_value' => isset($default_value[$plugin_id]) ? $default_value[$plugin_id] : [],
            '#states' => [
              'visible' => [
                ':input[name="' . $checkbox_name . '"]' => ['checked' => TRUE],
              ],
            ],
            // The element is already keyed by $plugin_id, no need to do it twice.
            '#enforce_unique_parents' => FALSE,
          ];
        }
      }
    }

    return $element;
  }

  /**
   * Builds the element name for the given parents.
   *
   * @param array $parents
   *   The parents.
   *
   * @return string
   *   The element name.
   */
  protected static function buildElementName(array $parents) {
    $name = array_shift($parents);
    $name .= '[' . implode('][', $parents) . ']';
    return $name;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(&$form, FormStateInterface $form_state) {
    $element_parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -1);
    return NestedArray::getValue($form, $element_parents);
  }

  /**
   * Submits the conditions.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitConditions(array &$element, FormStateInterface $form_state) {
    $value = [];
    // Transfer the configuration of each enabled plugin.
    foreach ($element['#categories'] as $category_id) {
      foreach (Element::getVisibleChildren($element[$category_id]) as $plugin_id) {
        $plugin_element = $element[$category_id][$plugin_id];
        $plugin_value = $form_state->getValue($plugin_element['#parents']);
        if ($plugin_value['enable']) {
          $value[] = [
            'plugin' => $plugin_id,
            'configuration' => $plugin_value['configuration'],
          ];
        }
      }
    }
    $form_state->setValueForElement($element, $value);
  }

}
