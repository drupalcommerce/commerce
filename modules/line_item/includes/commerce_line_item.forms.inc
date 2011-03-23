<?php

/**
 * @file
 * Functions for generating line item editing form to be used in line item views.
 */

/**
 * Generates a Views based line item edit form for line items on a single order.
 *
 * This form currently only supports line items from a single order, and it
 * determines which order the line items are for based on a Views argument.
 * Therefore, the View must include an Order ID argument or an empty form array
 * will be returned.
 */
function commerce_line_item_views_form($form, &$form_state, $view, $output) {
  // Require the existence of an order_id argument (and its value).
  if (empty($view->argument['order_id']) || empty($view->argument['order_id']->value)) {
    return array();
  }

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_line_item') . '/theme/commerce_line_item_views_form.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_line_item') . '/commerce_line_item.js';

  // Wrap the form in a div so we can add CSS and javascript behaviors to it.
  $form['#prefix'] = '<div class="commerce-line-item-views-form">';
  $form['#suffix'] = '</div>';

  // Load the order from the Views argument.
  $order = commerce_order_load($view->argument['order_id']->value[0]);
  $form_state['order'] = $order;

  // Add the form arguments to the form array so they are included when the form
  // array is passed to the theme function.
  $form['view'] = array(
    '#type' => 'value',
    '#value' => $view,
  );
  $form['output'] = array(
    '#type' => 'value',
    '#value' => $output,
  );

  // Add the handlers manually since we're using hook_forms() to associate this
  // form with form IDs based on the View used to display it.
  $form['#validate'][] = 'commerce_line_item_views_form_validate';
  $form['#submit'][] = 'commerce_line_item_views_form_submit';

  // Loop over each field on the View...
  foreach ($view->field as $field_name => $field) {
    // If it extends the line item field edit handler...
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      // Retrieve the form array for this item from the handler.
      $field_edit_form = $field->get_edit_form();

      // Add it to the current form if data was returned.
      if (!empty($field_edit_form)) {
        $form[$field_name] = $field_edit_form + array('#tree' => TRUE);
        // If the field has a #submit element, we add the form submit as well.
        foreach (element_children($form[$field_name]) as $line_item_id) {
          if (!empty($form[$field_name][$line_item_id]['#submit'])) {
            $form[$field_name][$line_item_id]['#submit'] = array_merge($form['#submit'], $form[$field_name][$line_item_id]['#submit']);
          }
        }
      }
    }
  }

  // Now add the submit buttons after setting the handlers, as it's likely that
  // buttons will need to add button specific handlers based on the form level
  // validate and submit handlers.
  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 100,
  );

  $form['actions']['update'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#submit' => array_merge($form['#submit'], array('commerce_line_item_line_item_views_form_submit')),
  );

  return $form;
}

/**
 * Validate handler for the line item views form.
 */
function commerce_line_item_views_form_validate($form, &$form_state) {
  $view = $form['view']['#value'];

  // Call the validation method on every field handler that extends
  // commerce_line_item_handler_field_edit.
  foreach ($view->field as $field_name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      $field->edit_form_validate($form, $form_state);
    }
  }
}

/**
 * Submit handler for the line item views form.
 */
function commerce_line_item_views_form_submit($form, &$form_state) {
  $view = $form['view']['#value'];

  // Call the submit method on every field handler that extends
  // commerce_line_item_handler_field_edit.
  foreach ($view->field as $field_name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      $field->edit_form_submit($form, $form_state);
    }
  }
}

/**
 * Themes the line item Views form.
 */
function theme_commerce_line_item_views_form($variables) {
  $form = $variables['form'];

  // No View provided, abort.
  if (empty($form['view'])) {
    return;
  }

  // Extract the form arguments from the form array.
  $view = $form['view']['#value'];
  $output = $form['output']['#value'];

  // For each field on the View that extends the edit field handler, prepare its
  // placeholder in the output to be replaced with the rendered form element
  // that should go there.
  $search = array();
  $replace = array();

  foreach ($view->field as $field_name => $field) {
    if(is_a($field, 'commerce_line_item_handler_field_edit')) {
      // Form elements have been prepared keyed by ID in association with the
      // Views field name, so loop over them and render the form element.
      foreach (element_children($form[$field_name]) as $line_item_id) {
        $search[] = '<!--post-commerce-line-item-' . $field_name . '-' . $line_item_id . '-->';
        $replace[] = drupal_render($form[$field_name][$line_item_id]);
      }
    }
  }

  // Add line item action submit buttons.
  $search[] = '<!--post-commerce-line-item-buttons-->';
  $replace[] = drupal_render($form['actions']);

  // Apply replacements to the rendered output.
  $output = str_replace($search, $replace, $output);

  // Render and add remaining form fields.
  $output .= drupal_render_children($form);

  return $output;
}
