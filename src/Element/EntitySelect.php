<?php

/**
 * @file
 * Contains \Drupal\commerce\Element\EntitySelect.
 */

namespace Drupal\commerce\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form input element for selecting one or multiple entities.
 *
 * If the element is required, and there's only one available entity, a hidden
 * form element is used. Otherwise the element is transformed based on just the
 * number of available entities:
 * 1..#autocomplete_threshold: Checkboxes/radios element, based on #multiple.
 * >#autocomplete_treshold: entity autocomplete element.
 *
 * Properties:
 * - #target_type: The entity type being selected.
 * - #multiple: Whether the user may select more than one item.
 * - #default_value: An entity ID or an array of entity IDs.
 * - #autocomplete_threshold: Determines when to use the autocomplete.
 * - #autocomplete_size: The size of the autocomplete element in characters.
 * - #autocomplete_placeholder: The placeholder for the autocomplete element.
 *
 * Example usage:
 * @code
 * $form['entities'] = [
 *   '#type' => 'entity_select',
 *   '#title' => t('Stores'),
 *   '#target_type' => 'commerce_store',
 *   '#multiple' => TRUE,
 * ];
 * @end
 *
 * @FormElement("entity_select")
 */
class EntitySelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#target_type' => '',
      '#multiple' => FALSE,
      '#autocomplete_threshold' => 7,
      '#autocomplete_size' => 60,
      '#autocomplete_placeholder' => '',
      '#process' => [
        [$class, 'processEntitySelect'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateEntitySelect'],
      ],
    ];
  }

  /**
   * Process callback.
   */
  public static function processEntitySelect(&$element, FormStateInterface $formState, &$completeForm) {
    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    $storage = \Drupal::service('entity_type.manager')->getStorage($element['#target_type']);
    $entityCount = $storage->getQuery()->count()->execute();
    $element['#tree'] = TRUE;
    // No need to show anything, there's only one possible value.
    if ($element['#required'] && $entityCount == 1) {
      $entityIds = $storage->getQuery()->execute();
      $element['value'] = [
        '#type' => 'hidden',
        '#value' => reset($entityIds),
      ];

      return $element;
    }

    if ($entityCount <= $element['#autocomplete_threshold']) {
      $entities = $storage->loadMultiple();
      $entityLabels = array_map(function ($entity) {
        return $entity->label();
      }, $entities);
      // Radio buttons don't have a None option by default.
      if (!$element['#multiple'] && !$element['#required']) {
        $entityLabels = ['' => t('None')] + $entityLabels;
      }

      $element['value'] = [
        '#type' => $element['#multiple'] ? 'checkboxes' : 'radios',
        '#required' => $element['#required'],
        '#options' => $entityLabels,
      ];
      if (!empty($element['#default_value'])) {
        $element['value']['#default_value'] = $element['#default_value'];
      }
    }
    else {
      $defaultValue = NULL;
      if (!empty($element['#default_value'])) {
        // Upcast ids into entities, as expected by entity_autocomplete.
        if ($element['#multiple']) {
          $defaultValue = $storage->loadMultiple($element['#default_value']);
        }
        else {
          $defaultValue = $storage->load($element['#default_value']);
        }
      }

      $element['value'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => $element['#target_type'],
        '#tags' => $element['#multiple'],
        '#required' => $element['#required'],
        '#default_value' => $defaultValue,
        '#size' => $element['#autocomplete_size'],
        '#placeholder' => $element['#autocomplete_placeholder'],
      ];
    }

    // These keys only make sense on the actual input element.
    foreach (['#title', '#title_display', '#description', '#ajax'] as $key) {
      if (isset($element[$key])) {
        $element['value'][$key] = $element[$key];
        unset($element[$key]);
      }
    }

    return $element;
  }

  /**
   * Validation callback.
   *
   * Transforms the subelement value into a consistent format and set it on the
   * main element.
   */
  public static function validateEntitySelect(&$element, FormStateInterface $formState, &$completeForm) {
    $valueElement = $element['value'];
    $value = $formState->getValue($valueElement['#parents']);
    if (is_array($value)) {
      if ($valueElement['#type'] == 'checkboxes') {
        // Remove unselected checkboxes.
        $value = array_filter($value);
      }
      elseif (!empty($valueElement['#tags'])) {
        // Extract the entity ids from a multivalue autocomplete.
        $value = array_column($value, 'target_id');
      }
    }
    $formState->setValueForElement($element, $value);
  }

}
