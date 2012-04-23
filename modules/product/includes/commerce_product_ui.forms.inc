<?php

/**
 * @file
 * Forms for creating / editing and deleting products.
 */


/**
 * Form callback: create or edit a product type.
 *
 * @param $product_type
 *   The product type array to edit or for a create form an empty product type
 *     array with properties instantiated but not populated.
 */
function commerce_product_ui_product_type_form($form, &$form_state, $product_type) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_product_ui') . '/includes/commerce_product_ui.forms.inc';

  // Store the initial product type in the form state.
  $form_state['product_type'] = $product_type;

  $form['product_type'] = array(
    '#tree' => TRUE,
  );

  $form['product_type']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $product_type['name'],
    '#description' => t('The human-readable name of this product type. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
    '#required' => TRUE,
    '#size' => 32,
  );

  if (empty($product_type['type'])) {
    $form['product_type']['type'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#default_value' => $product_type['type'],
      '#maxlength' => 32,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => 'commerce_product_type_load',
        'source' => array('product_type', 'name'),
      ),
      '#description' => t('The machine-readable name of this product type. This name must contain only lowercase letters, numbers, and underscores, it must be unique.'),
    );
  }

  $form['product_type']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#description' => t('Describe this product type. The text will be displayed on the <em>Add new content</em> page.'),
    '#default_value' => $product_type['description'],
    '#rows' => 3,
  );

  $form['product_type']['help'] = array(
    '#type' => 'textarea',
    '#title' => t('Explanation or submission guidelines'),
    '#description' => t('This text will be displayed at the top of the page when creating or editing products of this type.'),
    '#default_value' => $product_type['help'],
    '#rows' => 3,
  );

  $form['product_type']['revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default products of this type to be saved as new revisions when edited.'),
    '#default_value' => $product_type['revision'],
  );

  if (module_exists('entity_translation')) {
    $form['product_type']['multilingual'] = array(
      '#type' => 'radios',
      '#title' => t('Multilingual support'),
      '#description' => t('If <em>Entity translation</em> is enabled it will be possible to provide a different version of the same product for each available language.') . '<br />' . t('Existing products will not be affected by changing this option.'),
      '#options' => array(
        0 => t('Disabled'),
        ENTITY_TRANSLATION_ENABLED => t('Enabled via <em>Entity translation</em>'),
      ),
      '#default_value' => variable_get('language_product_type_' . $product_type['type'], 0),
    );
  }

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 40,
  );

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save product type'),
    '#submit' => array_merge($submit, array('commerce_product_ui_product_type_form_submit')),
  );

  if (!empty($form_state['product_type']['type'])) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete product type'),
      '#suffix' => l(t('Cancel'), 'admin/commerce/products/types'),
      '#submit' => array_merge($submit, array('commerce_product_ui_product_type_form_delete_submit')),
      '#weight' => 45,
    );
  }
  else {
    $form['actions']['save_continue'] = array(
      '#type' => 'submit',
      '#value' => t('Save and add fields'),
      '#suffix' => l(t('Cancel'), 'admin/commerce/products/types'),
      '#submit' => array_merge($submit, array('commerce_product_ui_product_type_form_submit')),
      '#weight' => 45,
    );
  }

  $form['#validate'][] = 'commerce_product_ui_product_type_form_validate';

  return $form;
}

/**
 * Validation callback for commerce_product_product_type_form().
 */
function commerce_product_ui_product_type_form_validate($form, &$form_state) {
  $product_type = $form_state['product_type'];

  // If saving a new product type, ensure it has a unique machine name.
  if (empty($product_type['type'])) {
    if (!commerce_product_ui_validate_product_type_unique($form_state['values']['product_type']['type'])) {
      form_set_error('product_type][type', t('The machine name specified is already in use.'));
    }
  }
}

/**
 * Form submit handler: save a product type.
 */
function commerce_product_ui_product_type_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];
  $updated = !empty($product_type['type']);

  // If a type is set, we should still check to see if a row for the type exists
  // in the database; this is done to accomodate types defined by Features.
  if ($updated) {
    $updated = db_query('SELECT 1 FROM {commerce_product_type} WHERE type = :type', array(':type' => $product_type['type']))->fetchField();
  }

  foreach ($form_state['values']['product_type'] as $key => $value) {
    $product_type[$key] = $value;
  }

  // Write the product type to the database.
  $product_type['is_new'] = !$updated;
  commerce_product_ui_product_type_save($product_type);

  // Set the multingual value for the product type if entity translation is enabled.
  if (module_exists('entity_translation')) {
    variable_set('language_product_type_' . $product_type['type'], $product_type['multilingual']);
  }

  // Redirect based on the button clicked.
  drupal_set_message(t('Product type saved.'));

  if ($form_state['triggering_element']['#parents'][0] == 'save_continue') {
    $form_state['redirect'] = 'admin/commerce/products/types/' . strtr($product_type['type'], '_', '-') . '/fields';
  }
  else {
    $form_state['redirect'] = 'admin/commerce/products/types';
  }
}

/**
 * Submit callback for delete button on commerce_product_ui_product_type_form().
 *
 * @see commerce_product_ui_product_type_form()
 */
function commerce_product_ui_product_type_form_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/commerce/products/types/' . strtr($form_state['product_type']['type'], '_', '-') . '/delete';
}

/**
 * Form callback: confirmation form for deleting a product type.
 *
 * @param $product_type
 *   The product type array to be deleted.
 *
 * @see confirm_form()
 */
function commerce_product_ui_product_type_delete_form($form, &$form_state, $product_type) {
  $form_state['product_type'] = $product_type;

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_product_ui') . '/includes/commerce_product_ui.forms.inc';

  $form['#submit'][] = 'commerce_product_ui_product_type_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete the %name product type?', array('%name' => $product_type['name'])),
    'admin/commerce/products/types',
    '<p>' . t('This action cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_product_product_type_delete_form().
 */
function commerce_product_ui_product_type_delete_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];

  commerce_product_ui_product_type_delete($product_type['type']);

  drupal_set_message(t('The product type %name has been deleted.', array('%name' => $product_type['name'])));
  watchdog('commerce_product', 'Deleted product type %name.', array('%name' => $product_type['name']), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/commerce/products/types';
}
