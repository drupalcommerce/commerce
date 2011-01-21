<?php
// $Id$

/**
 * @file
 * Functions for generating line item editing form to be used in line item views.
 */

/**
 * Generate array with form to be used on a view for editing a line item.
 */
function commerce_line_item_edit_form($form, &$form_state, $view, $output) {
  // Check the existence of order_id argument.
  if (!isset($view->argument['order_id'])) {
    return;
  }

  // Load the cart order.
  // TODO: Make this order independent?
  $order = commerce_order_load($view->argument['order_id']->value[0]);

  // Add a wrapper div to the form so we can add css and javascript behaviors to
  // it.
  $form['#prefix'] = '<div class="commerce-line-item-edit-form">';
  $form['#suffix'] = '</div>';

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_line_item') . '/theme/commerce_line_item.edit_form.css';

  $form['#submit'] = array('commerce_line_item_edit_form_submit');

  // Pass order to submit handler.
  $form['order'] = array(
    '#type' => 'value',
    '#value' => $order,
  );

  // Pass view for themeing.
  $form['view'] = array(
    '#type' => 'value',
    '#value' => $view,
  );

  // Pass output for replacing tokens with form fields.
  $form['output'] = array(
    '#type' => 'value',
    '#value' => $output,
  );

  foreach ($view->field as $name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      $field_edit_form = $field->get_edit_form();
      if (!empty($field_edit_form)) {
        $form[$name] = $field_edit_form + array('#tree' => TRUE);
      }
    }
  }

  // Move actions to views content plugin.
  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 100,
  );

  // Add buttons that'll be used to replace tokens on line item actions area
  // handler.
  foreach (commerce_line_item_actions() as $name => $button) {
    $form['actions'][$name] = $button;
  }

  return $form;
}

/**
 * Validate handler for the cart operations.
 */
function commerce_line_item_edit_form_validate($form, &$form_state) {
  $view = $form['view']['#value'];

  // Call edit_form_validate() method on every form which extends
  // commerce_line_item_handler_field_edit.
  foreach ($view->field as $name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      $field->edit_form_validate($form, $form_state);
    }
  }
}

/**
 * Submit handler for the cart operations.
 */
function commerce_line_item_edit_form_submit($form, &$form_state) {
  $view = $form['view']['#value'];

  // Call edit_form_submit() method on every form which extends
  // commerce_line_item_handler_field_edit.
  foreach ($view->field as $name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      $field->edit_form_submit($form, $form_state);
    }
  }
}

/**
 * Themes the editable line item form.
 */
function theme_commerce_line_item_edit_form($variables) {
  $form = $variables['form'];
  $view = $form['view']['#value'];
  $output = $form['output']['#value'];

  $search = array();
  $replace = array();
  foreach ($view->field as $name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      foreach (element_children($form[$name]) as $line_item_id) {
        $search[] = '<!--post-commerce-line-item-' . $name . '-' . $line_item_id . '-->';
        $replace[] = drupal_render($form[$name][$line_item_id]);
      }
    }
  }

  // Add line items summary are handler buttons.
  foreach (commerce_line_item_actions() as $name => $button) {
    $search[] = '<!--post-commerce-line-item-button-' . $name . '-->';
    $replace[] = drupal_render($form['actions'][$name]);
  }

  // Apply replacements to the rendered output.
  $output = str_replace($search, $replace, $output);

  // Render and add remaining form fields.
  $output .= drupal_render_children($form);

  return $output;
}
