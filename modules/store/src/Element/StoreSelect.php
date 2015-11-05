<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Element\StoreSelect.
 */

namespace Drupal\commerce_store\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form input element for selecting one or multiple stores.
 *
 * The element is transformed based on the number of available stores:
 * 1 store: Hidden form element.
 * 1..#autocomplete_threshold: Checkboxes/radios element, based on #multiple.
 * >#autocomplete_treshold: entity autocomplete element.
 *
 * Properties:
 * - #default_value: A store ID or an array of store IDs.
 * - #multiple: Whether the user may select more than one item.
 * - #autocomplete_threshold: Determines when to use the autocomplete.
 *
 * Example usage:
 * @code
 * $form['stores'] = [
 *   '#type' => 'store_select',
 *   '#title' => t('Stores'),
 *   '#multiple' => TRUE,
 * ];
 * @end
 *
 * @FormElement("store_select")
 */
class StoreSelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#autocomplete_threshold' => 7,
      '#process' => [
        [$class, 'processStoreSelect'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateStoreSelect'],
      ],
    ];
  }

  /**
   * Process callback.
   */
  public static function processStoreSelect(&$element, FormStateInterface $formState, &$completeForm) {
    $storeStorage = \Drupal::service('entity_type.manager')->getStorage('commerce_store');
    $storeCount = $storeStorage->getQuery()->count()->execute();
    $element['#tree'] = TRUE;
    // No need to show anything, there's only one possible value.
    if ($element['#required'] && $storeCount == 1) {
      $storeIds = $storeStorage->getQuery()->execute();
      $element['value'] = [
        '#type' => 'hidden',
        '#value' => reset($storeIds),
      ];

      return $element;
    }

    if ($storeCount <= $element['#autocomplete_threshold']) {
      $stores = $storeStorage->loadMultiple();
      $storeLabels = array_map(function ($store) {
        return $store->label();
      }, $stores);
      // Radio buttons don't have a None option by default.
      if (!$element['#multiple'] && !$element['#required']) {
        $storeLabels = ['' => t('None')] + $storeLabels;
      }

      $element['value'] = [
        '#type' => $element['#multiple'] ? 'checkboxes' : 'radios',
        '#required' => $element['#required'],
        '#default_value' => $element['#default_value'],
        '#options' => $storeLabels,
      ];
    }
    else {
      $defaultValue = NULL;
      // Upcast ids into entities, as expected by entity_autocomplete.
      if ($element['#default_value']) {
        if ($element['#multiple']) {
          $defaultValue = $storeStorage->loadMultiple($element['#default_value']);
        }
        else {
          $defaultValue = $storeStorage->load($element['#default_value']);
        }
      }

      $element['value'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'commerce_store',
        '#tags' => $element['#multiple'],
        '#required' => $element['#required'],
        '#default_value' => $defaultValue,
      ];
    }

    // These keys only make sense on the actual input element.
    $transferKeys = [
      '#title', '#title_display', '#description', '#ajax', '#placeholder',
    ];
    foreach ($transferKeys as $key) {
      if (isset($element[$key])) {
        $element['value'][$key] = $element[$key];
        unset($element[$key]);
      }
    }

    return $element;
  }

  /**
   * Validation callback.
   */
  public static function validateStoreSelect(&$element, FormStateInterface $formState, &$completeForm) {
    // Transfer the value from the subelement.
    $elementValue = $formState->getValue($element['#parents']);
    $formState->setValueForElement($element, $elementValue['value']);
  }

}
