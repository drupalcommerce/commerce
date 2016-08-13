<?php

namespace Drupal\commerce_product\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

class ProductAttributeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute */
    $attribute = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $attribute->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $attribute->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductAttribute::load',
      ],
      // Attribute field names are constructed as 'attribute_' + id, and must
      // not be longer than 32 characters. Account for that prefix length here.
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH - 10,
    ];
    $form['elementType'] = [
      '#type' => 'select',
      '#title' => $this->t('Element type'),
      '#description' => $this->t('Controls how the attribute is displayed on the add to cart form.'),
      '#options' => [
        'radios' => $this->t('Radio buttons'),
        'select' => $this->t('Select list'),
        'commerce_product_rendered_attribute' => $this->t('Rendered attribute'),
      ],
      '#default_value' => $attribute->getElementType(),
    ];
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $enabled = TRUE;
      if (!$attribute->isNew()) {
        $translation_manager = \Drupal::service('content_translation.manager');
        $enabled = $translation_manager->isEnabled('commerce_product_attribute_value', $attribute->id());
      }
      $form['enable_value_translation'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable attribute value translation'),
        '#default_value' => $enabled,
      ];
    }
    // The attribute acts as a bundle for attribute values, so the values can't
    // be created until the attribute is saved.
    if (!$attribute->isNew()) {
      $form = $this->buildValuesForm($form, $form_state);
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * Builds the attribute values form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The attribute values form.
   */
  public function buildValuesForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute */
    $attribute = $this->entity;
    $values = $attribute->getValues();
    $user_input = $form_state->getUserInput();
    // Reorder the values by name, if requested.
    if ($form_state->get('reset_alphabetical')) {
      $value_names = array_map(function ($value) {
        return $value->label();
      }, $values);
      asort($value_names);
      foreach (array_keys($value_names) as $weight => $id) {
        $values[$id]->setWeight($weight);
      }
    }
    // The value map allows new values to be added and removed before saving.
    // An array in the $index => $id format. $id is '_new' for unsaved values.
    $value_map = (array) $form_state->get('value_map');
    if (empty($value_map)) {
      $value_map = $values ? array_keys($values) : ['_new'];
      $form_state->set('value_map', $value_map);
    }

    $wrapper_id = Html::getUniqueId('product-attribute-values-ajax-wrapper');
    $form['values'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Value'), 'colspan' => 2],
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'product-attribute-value-order-weight',
        ],
      ],
      '#weight' => 5,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // #input defaults to TRUE, which breaks file fields in the IEF element.
      // This table is used for visual grouping only, the element itself
      // doesn't have any values of its own that need processing.
      '#input' => FALSE,
    ];
    // Make the weight list always reflect the current number of values.
    // Taken from WidgetBase::formMultipleElements().
    $max_weight = count($value_map);

    foreach ($value_map as $index => $id) {
      $value_form = &$form['values'][$index];
      // The tabledrag element is always added to the first cell in the row,
      // so we add an empty cell to guide it there, for better styling.
      $value_form['#attributes']['class'][] = 'draggable';
      $value_form['tabledrag'] = [
        '#markup' => '',
      ];

      $value_form['entity'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => 'commerce_product_attribute_value',
        '#bundle' => $attribute->id(),
        '#langcode' => $attribute->get('langcode'),
        '#save_entity' => FALSE,
      ];
      if ($id == '_new') {
        $default_weight = $max_weight;
        $remove_access = TRUE;
      }
      else {
        $value = $values[$id];
        $value_form['entity']['#default_value'] = $value;
        $default_weight = $value->getWeight();
        $remove_access = $value->access('delete');
      }

      $value_form['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#delta' => $max_weight,
        '#default_value' => $default_weight,
        '#attributes' => [
          'class' => ['product-attribute-value-order-weight'],
        ],
      ];
      // Used by SortArray::sortByWeightProperty to sort the rows.
      if (isset($user_input['values'][$index])) {
        $input_weight = $user_input['values'][$index]['weight'];
        // If the weights were just reset, reflect it in the user input.
        if ($form_state->get('reset_alphabetical')) {
          $input_weight = $default_weight;
        }
        // Make sure the weight is not out of bounds due to removals.
        if ($user_input['values'][$index]['weight'] > $max_weight) {
          $input_weight = $max_weight;
        }
        // Reflect the updated user input on the element.
        $value_form['weight']['#value'] = $input_weight;

        $value_form['#weight'] = $input_weight;
      }
      else {
        $value_form['#weight'] = $default_weight;
      }

      $value_form['remove'] = [
        '#type' => 'submit',
        '#name' => 'remove_value' . $index,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeValueSubmit'],
        '#value_index' => $index,
        '#ajax' => [
          'callback' => '::valuesAjax',
          'wrapper' => $wrapper_id,
        ],
        '#access' => $remove_access,
      ];
    }

    // Sort the values by weight. Ensures weight is preserved on ajax refresh.
    uasort($form['values'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    $access_handler = $this->entityTypeManager->getAccessControlHandler('commerce_product_attribute_value');
    if ($access_handler->createAccess($attribute->id())) {
      $form['values']['_add_new'] = [
        '#tree' => FALSE,
      ];
      $form['values']['_add_new']['entity'] = [
        '#type' => 'container',
        '#wrapper_attributes' => ['colspan' => 2],
      ];
      $form['values']['_add_new']['entity']['add_value'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add value'),
        '#submit' => ['::addValueSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::valuesAjax',
          'wrapper' => $wrapper_id,
        ],
      ];
      $form['values']['_add_new']['entity']['reset_alphabetical'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset to alphabetical'),
        '#submit' => ['::resetAlphabeticalSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::valuesAjax',
          'wrapper' => $wrapper_id,
        ],
      ];
      $form['values']['_add_new']['operations'] = [
        'data' => [],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback for value operations.
   */
  public function valuesAjax(array $form, FormStateInterface $form_state) {
    return $form['values'];
  }

  /**
   * Submit callback for adding a new value.
   */
  public function addValueSubmit(array $form, FormStateInterface $form_state) {
    $value_map = (array) $form_state->get('value_map');
    $value_map[] = '_new';
    $form_state->set('value_map', $value_map);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for resetting attribute value ordering to alphabetical.
   */
  public function resetAlphabeticalSubmit(array $form, FormStateInterface $form_state) {
    $form_state->set('reset_alphabetical', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a value.
   */
  public function removeValueSubmit(array $form, FormStateInterface $form_state) {
    $value_index = $form_state->getTriggeringElement()['#value_index'];
    $value_map = (array) $form_state->get('value_map');
    $value_id = $value_map[$value_index];
    unset($value_map[$value_index]);
    $form_state->set('value_map', $value_map);
    // Non-new values also need to be deleted from storage.
    if ($value_id != '_new') {
      $delete_queue = (array) $form_state->get('delete_queue');
      $delete_queue[] = $value_id;
      $form_state->set('delete_queue', $delete_queue);
    }
    $form_state->setRebuild();
  }

  /**
   * Saves the attribute values.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function saveValues(array $form, FormStateInterface $form_state) {
    $delete_queue = $form_state->get('delete_queue');
    if (!empty($delete_queue)) {
      $value_storage = $this->entityTypeManager->getStorage('commerce_product_attribute_value');
      $values = $value_storage->loadMultiple($delete_queue);
      $value_storage->delete($values);
    }

    foreach ($form_state->getValue(['values']) as $index => $value_data) {
      /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value */
      $value = $form['values'][$index]['entity']['#entity'];
      $value->setWeight($value_data['weight']);
      $value->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $translation_manager = \Drupal::service('content_translation.manager');
      // Logic from content_translation_language_configuration_element_submit().
      $enabled = $form_state->getValue('enable_value_translation');
      if ($translation_manager->isEnabled('commerce_product_attribute_value', $this->entity->id()) != $enabled) {
        $translation_manager->setEnabled('commerce_product_attribute_value', $this->entity->id(), $enabled);
        $this->entityTypeManager->clearCachedDefinitions();
        \Drupal::service('router.builder')->setRebuildNeeded();
      }
    }

    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Created the %label product attribute.', ['%label' => $this->entity->label()]));
      // Send the user to the edit form to create the attribute values.
      $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
    }
    else {
      $this->saveValues($form, $form_state);
      drupal_set_message($this->t('Updated the %label product attribute.', ['%label' => $this->entity->label()]));
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
  }

}
