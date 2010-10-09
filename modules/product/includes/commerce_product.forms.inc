<?php
// $Id$

/**
 * @file
 * Forms for creating / editing and deleting products.
 */


/**
 * Form callback: create or edit a product type.
 *
 * @param $product_type
 *   The product type object to edit or for a create form an empty product type
 *     object with properties instantiated but not populated.
 */
function commerce_product_product_type_form($form, &$form_state, $product_type) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_product') . '/includes/commerce_product.forms.inc';

  // Store the initial product type in the form state.
  $form_state['product_type'] = $product_type;

  $form['product_type'] = array(
    '#tree' => TRUE,
  );

  $form['product_type']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $product_type->name,
    '#description' => t('The human-readable name of this product type. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
    '#required' => TRUE,
    '#size' => 32,
    '#field_suffix' => ' <small id="edit-product-type-name-suffix">' . $product_type->type . '</small>',
  );

  if (empty($product_type->type)) {
    $js_settings = array(
      'type' => 'setting',
      'data' => array(
        'machineReadableValue' => array(
          'product-type-name' => array(
            'text' => t('Machine name'),
            'target' => 'product-type-type',
            'searchPattern' => '[^a-z0-9]+',
            'replaceToken' => '_',
          ),
        ),
      ),
    );
    $form['product_type']['type'] = array(
      '#type' => 'textfield',
      '#title' => t('Machine name'),
      '#default_value' => $product_type->type,
      '#maxlength' => 32,
      '#required' => TRUE,
      '#description' => t('The machine-readable name of this product type. This name must contain only lowercase letters, numbers, and underscores, it must be unique.'),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'system') . '/system.js', $js_settings),
      ),
    );
  }

  $form['product_type']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#description' => t('Describe this product type. The text will be displayed on the <em>Add new content</em> page.'),
    '#default_value' => $product_type->description,
    '#rows' => 3,
  );

  $form['product_type']['help'] = array(
    '#type' => 'textarea',
    '#title' => t('Explanation or submission guidelines'),
    '#description' => t('This text will be displayed at the top of the page when creating or editing products of this type.'),
    '#default_value' => $product_type->help,
    '#rows' => 3,
  );

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
    '#submit' => $submit + array('commerce_product_product_type_form_submit'),
  );

  $form['#validate'][] = 'commerce_product_product_type_form_validate';

  return $form;
}

/**
 * Validation callback for commerce_product_product_type_form().
 */
function commerce_product_product_type_form_validate($form, &$form_state) {
  $product_type = $form_state['product_type'];

  // If saving a new product type, ensure it has a unique machine name.
  if (empty($product_type->type)) {
    if (!commerce_product_type_validate_type_unique($form_state['values']['product_type']['type'])) {
      form_set_error('product_type][type', 'The machine name specified is already in use.');
    }
  }
}

/**
 * Form submit handler: save a product type.
 */
function commerce_product_product_type_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];
  $updated = !empty($product_type->type);

  foreach ($form_state['values']['product_type'] as $key => $value) {
    $product_type->$key = $value;
  }

  // Write the product type to the database.
  $product_type->is_new = !$updated;
  commerce_product_type_save($product_type);

  // Redirect based on the button clicked.
  drupal_set_message(t('Product type saved.'));
}

/**
 * Form callback: confirmation form for deleting a product type.
 *
 * @param $product_type
 *   The product type object to be deleted.
 *
 * @see confirm_form()
 */
function commerce_product_product_type_delete_form($form, &$form_state, $product_type) {
  $form_state['product_type'] = $product_type;

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_product') . '/includes/commerce_product.forms.inc';

  $form['#submit'][] = 'commerce_product_product_type_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete the %name product type?', array('%name' => $product_type->name)),
    '',
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
function commerce_product_product_type_delete_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];

  commerce_product_type_delete($product_type->type);

  drupal_set_message(t('The product type %name has been deleted.', array('%name' => $product_type->name)));
  watchdog('commerce_product', 'Deleted product type %name.', array('%name' => $product_type->name), WATCHDOG_NOTICE);
}

/**
 * Form callback: create or edit a product.
 *
 * @param $product
 *   The product object to edit or for a create form an empty product object
 *     with only a product type defined.
 */
function commerce_product_product_form($form, &$form_state, $product) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_product') . '/includes/commerce_product.forms.inc';

  // Add the default field elements.
  // TODO: Update description to include the acceptable product tokens.
  $form['sku'] = array(
    '#type' => 'textfield',
    '#title' => t('Product SKU'),
    '#description' => t('Supply a unique identifier for this product using letters, numbers, hypens, and underscores.'),
    '#default_value' => $product->sku,
    '#maxlength' => 128,
    '#required' => TRUE,
    '#weight' => -10,
  );

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $product->title,
    '#maxlength' => 255,
    '#required' => TRUE,
    '#weight' => -5,
  );

  // Add the field related form elements.
  $form_state['commerce_product'] = $product;
  field_attach_form('commerce_product', $product, $form, $form_state);

  $form['status'] = array(
    '#type' => 'radios',
    '#title' => t('Status'),
    '#description' => t('Disabled products cannot be added to shopping carts and may be hidden in administrative product lists.'),
    '#options' => array(
      '1' => t('Active'),
      '0' => t('Disabled'),
    ),
    '#default_value' => $product->status,
    '#required' => TRUE,
    '#weight' => 35,
  );

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
    '#value' => t('Save product'),
    '#submit' => $submit + array('commerce_product_product_form_submit'),
  );

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'commerce_product_product_form_validate';

  return $form;
}

/**
 * Validation callback for commerce_product_product_form().
 */
function commerce_product_product_form_validate($form, &$form_state) {
  $product = $form_state['commerce_product'];

  // TODO: Resolve workflow issues pertaining to token replacement in SKUs.
  // Perform token replacement on the entered SKU.
  // $sku = commerce_product_replace_sku_tokens($form_state['values']['sku'], $product);

  // Until the above is resolved, simply use the SKU as entered with no tokens.
  $sku = $form_state['values']['sku'];

  // If invalid tokens were specified, throw an error.
  if ($sku === FALSE) {
    form_set_error('sku', 'The SKU contains invalid tokens.');
  }
  else {
    // Validate the remaining characters of the string.
    if (!commerce_product_validate_sku_characters($sku)) {
      form_set_error('sku', t('The SKU must contain only letters, numbers, hyphens, and underscores.'));
    }

    // Ensure the proposed SKU is unique.
    if (!commerce_product_validate_sku_unique($sku, $product->product_id)) {
      form_set_error('sku', 'This SKU is already in use and must be unique. Please supply another value.');
    }
  }

  // Notify field widgets to validate their data.
  field_attach_form_validate('commerce_product', $product, $form, $form_state);
}

/**
 * Submit callback for commerce_product_product_form().
 */
function commerce_product_product_form_submit($form, &$form_state) {
  global $user;

  $product = &$form_state['commerce_product'];

  // Save default parameters back into the $product object.
  $product->sku = $form_state['values']['sku'];
  $product->title = $form_state['values']['title'];
  $product->status = $form_state['values']['status'];

  // Set the product's uid if it's being created at this time.
  if (empty($product->product_id)) {
    $product->uid = $user->uid;
  }

  // Notify field widgets.
  field_attach_submit('commerce_product', $product, $form, $form_state);

  // Save the product.
  commerce_product_save($product);

  // Redirect based on the button clicked.
  drupal_set_message(t('Product saved.'));
}

/**
 * Form callback: confirmation form for deleting a product.
 *
 * @param $product
 *   The product object to be deleted.
 *
 * @see confirm_form()
 */
function commerce_product_product_delete_form($form, &$form_state, $product) {
  $form_state['product'] = $product;

  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['files']['form'] = drupal_get_path('module', 'commerce_product') . '/includes/commerce_product.forms.inc';

  $form['#submit'][] = 'commerce_product_product_delete_form_submit';

  // TODO: Replace the SKU in the description parameter with the product view
  // per http://drupal.org/node/733044.

  $form = confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $product->title)),
    '',
    '<p>' . t('SKU: @sku. Deleting this product cannot be undone.', array('@sku' => $product->sku)) . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_product_product_delete_form().
 */
function commerce_product_product_delete_form_submit($form, &$form_state) {
  $product = $form_state['product'];

  if (commerce_product_delete($product->product_id)) {
    drupal_set_message(t('%title has been deleted.', array('%title' => $product->title)));
    watchdog('commerce_product', 'Deleted product %title (SKU: @sku).', array('%title' => $product->title, '@sku' => $product->sku), WATCHDOG_NOTICE);
  }
  else {
    drupal_set_message(t('%title could not be deleted.', array('%title' => $product->title)), 'error');
  }
}
