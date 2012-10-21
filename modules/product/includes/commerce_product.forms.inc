<?php

/**
 * @file
 * Forms for creating, editing, and deleting products.
 */


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
    '#description' => t('Supply a unique identifier for this product using letters, numbers, hyphens, and underscores. Commas may not be used.'),
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
  $langcode = entity_language('commerce_product', $product);
  field_attach_form('commerce_product', $product, $form, $form_state, $langcode);

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

  // Load the product type to get the default revision setting.
  $product_type = commerce_product_type_load($product->type);

  // When updating a product, do not collapse the Change History fieldset if the
  // product type is configured to create a new revision by default.
  $form['change_history'] = array(
    '#type' => 'fieldset',
    '#title' => t('Change history'),
    '#collapsible' => TRUE,
    '#collapsed' => empty($product->product_id) || empty($product_type['revision']),
    '#weight' => 50,
  );
  if (!empty($product->product_id)) {
    $form['change_history']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision on update'),
      '#description' => t('If an update log message is entered, a revision will be created even if this is unchecked.'),
      '#default_value' => $product_type['revision'],
      '#access' => user_access('administer commerce_product entities'),
    );
  }
  $form['change_history']['log'] = array(
    '#type' => 'textarea',
    '#title' => !empty($product->product_id) ? t('Update log message') : t('Creation log message'),
    '#rows' => 4,
    '#description' => t('Provide an explanation of the changes you are making. This will provide a meaningful history of changes to this product.'),
  );

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 400,
  );

  // Simply use default language
  $form['language'] = array(
    '#type' => 'value',
    '#value' => $langcode,
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
    '#submit' => array_merge($submit, array('commerce_product_product_form_submit')),
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
    form_set_error('sku', t('The SKU contains invalid tokens.'));
  }
  else {
    // Ensure the proposed SKU is unique or reused only during product updates.
    $query = new EntityFieldQuery();

    $query
      ->entityCondition('entity_type', 'commerce_product')
      ->propertyCondition('sku', $sku);

    $result = $query->execute();

    if (!empty($result)) {
      $product_id = key($result['commerce_product']);

      if ($product_id != $product->product_id) {
        form_set_error('sku', t('This SKU is <a href="!url">already in use</a> and must be unique. Please supply another value.', array('!url' => url('admin/commerce/products/' . $product_id))));
      }
    }

    // Validate the SKU for invalid characters.
    if (!commerce_product_validate_sku($sku)) {
      form_set_error('sku', t('The SKU %sku contains invalid characters.', array('%sku' => $sku)));
    }

    // Trim leading and trailing whitespace from the SKU.
    form_set_value($form['sku'], trim($sku), $form_state);
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
  $product->language = $form_state['values']['language'];

  // Set the product's uid if it's being created at this time.
  if (empty($product->product_id)) {
    $product->uid = $user->uid;
  }

  // Trigger a new revision if the checkbox was enabled or a log message supplied.
  if ((user_access('administer commerce_product entities') && !empty($form_state['values']['revision'])) ||
    (!user_access('administer commerce_product entities') && !empty($form['change_history']['revision']['#default_value'])) ||
    !empty($form_state['values']['log'])) {
    $product->revision = TRUE;
    $product->log = $form_state['values']['log'];
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

  $content = entity_view('commerce_product', array($product->product_id => $product));

  $form = confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $product->title)),
    '',
    drupal_render($content) . '<p>' . t('Deleting this product cannot be undone.', array('@sku' => $product->sku)) . '</p>',
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
