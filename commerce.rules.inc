<?php

/**
 * @file
 * Rules integration for Drupal Commerce.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_condition_info_alter().
 * @todo remove "entity_exists" condition when http://drupal.org/node/1031530
 * is fixed.
 */
function commerce_rules_condition_info_alter(&$conditions) {
  // If the Rules module hasn't already provided "entity_exists", we add it here.
  if (!isset($conditions['entity_exists'])) {
    module_load_include('inc', 'rules', 'modules/data.rules');

    $conditions['entity_exists'] = array(
      'label' => t('Entity exists by property'),
      'parameter' => array(
        'type' => array(
          'type' => 'text',
          'label' => t('Entity type'),
          'options list' => 'commerce_entity_type_options_list',
          'description' => t('Specifies the type of the entity that should be fetched.'),
          'restriction' => 'input',
         ),
        'property' => array(
          'type' => 'text',
          'label' => t('Property'),
          'description' => t('The property by which the entity is to be selected.'),
          'restriction' => 'input',
         ),
        'value' => array(
          'type' => 'text',
          'label' => t('Value'),
          'description' => t('The property value of the entity to be fetched.'),
        ),
      ),
      'group' => t('Entities'),
      'base' => 'commerce_condition_entity_exists',
      'module' => 'commerce',
      'callbacks' => array(
        'form_alter' => 'rules_action_type_form_alter',
      ),
    );
  }
}

/**
 * Returns options containing entity types having the given key set in the info.
 */
function commerce_entity_type_options_list() {
  $types = array();

  foreach (entity_get_info() as $type => $entity_info) {
    $types[$type] = $entity_info['label'];
  }

  return $types;
}

/**
 * Condition callback: checks to see if an entity exists with the matching
 *   property value.
 */
function commerce_condition_entity_exists($type, $property, $value) {
  $result = entity_property_query($type, $property, $value, 1);
  return !empty($result);
}

/**
 * Info alteration callback for the entity query action.
 */
function commerce_condition_entity_exists_info_alter(&$element_info, RulesAbstractPlugin $element) {
  $element->settings += array('type' => NULL, 'property' => NULL);
  if ($element->settings['type']) {
    $element_info['parameter']['property']['options list'] = 'commerce_condition_entity_exists_property_options_list';

    if ($element->settings['property']) {
      $wrapper = entity_metadata_wrapper($element->settings['type']);
      if (isset($wrapper->{$element->settings['property']}) && $property = $wrapper->{$element->settings['property']}) {
        $element_info['parameter']['value']['type'] = $property->type();
        $element_info['parameter']['value']['options list']  = $property->optionsList() ? 'commerce_condition_entity_exists_value_options_list' : FALSE;
      }
    }
  }
}

/**
 * Returns the options list for choosing a property of an entity type.
 */
function commerce_condition_entity_exists_property_options_list(RulesAbstractPlugin $element) {
  $element->settings += array('type' => NULL);
  if ($element->settings['type']) {
    $properties = entity_get_all_property_info($element->settings['type']);
    return rules_extract_property($properties, 'label');
  }
}

/**
 * Returns the options list specified for the chosen property.
 */
function commerce_condition_entity_exists_value_options_list(RulesAbstractPlugin $element) {
  // Get the possible values for the selected property.
  $element->settings += array('type' => NULL, 'property' => NULL);
  if ($element->settings['type'] && $element->settings['property']) {
    $wrapper = entity_metadata_wrapper($element->settings['type']);

    if (isset($wrapper->{$element->settings['property']}) && $property = $wrapper->{$element->settings['property']}) {
      return $property->optionsList('view');
    }
  }
}

/**
 * Custom validate callback for data query action.
 */
function commerce_condition_entity_exists_validate($element) {
  if (!isset($element->settings['type'])) {
    throw new RulesEvaluationException('Invalid type specified.', array(), array($element, 'parameter', 'type'));
  }
  if (!isset($element->settings['property'])) {
    throw new RulesEvaluationException('Invalid property specified.', array(), array($element, 'parameter', 'property'));
  }
}

/**
 * @}
 */
