<?php
// $Id$

/**
 * Menu callback: display a list of product types that the user can create.
 */
function commerce_product_ui_add_page() {
  $item = menu_get_item();
  $content = system_admin_menu_block($item);

  // Bypass the admin/commerce/products/add listing if only one product type is
  // available.
  if (count($content) == 1) {
    $item = array_shift($content);
    drupal_goto($item['href']);
  }

  return theme('product_add_list', array('content' => $content));
}

/**
 * Display the list of available product types for product creation.
 *
 * @ingroup themeable
 */
function theme_product_add_list($variables) {
  $content = $variables['content'];
  $output = '';

  if ($content) {
    $output = '<dl class="commerce-product-type-list">';
    foreach ($content as $item) {
      $output .= '<dt>' . l($item['title'], $item['href'], $item['localized_options']) . '</dt>';
      $output .= '<dd>' . filter_xss_admin($item['description']) . '</dd>';
    }
    $output .= '</dl>';
  }
  else {
    if (user_access('administer product types')) {
      $output = '<p>' . t('You have not created any product types yet. Go to the <a href="@create-product-type">product type creation page</a> to add a new product type.', array('@create-product-type' => url('admin/commerce/products/types/add'))) . '</p>';
    }
    else {
      $output = '<p>' . t('No product types have been created yet for you to use.') . '</p>';
    }
  }

  return $output;
}

/**
 * Form callback: create or edit a product.
 */
function commerce_product_ui_product_form($form, &$form_state, $product) {
  $form = array();

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
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save product'),
  );

  // Add the save and continue button for new products.
  if (empty($product->product_id)) {
    $form['actions']['save_continue'] = array(
      '#type' => 'submit',
      '#value' => t('Save and add another'),
      '#suffix' => l('Cancel', 'admin/commerce/products'),
      '#weight' => 45,
    );
  }
  else {
    $form['actions']['submit']['#suffix'] = l('Cancel', 'admin/commerce/products');
  }

  return $form;
}

/**
 * Validation callback for commerce_product_ui_form().
 */
function commerce_product_ui_product_form_validate($form, &$form_state) {
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
 * Submit callback for commerce_product_ui_form().
 */
function commerce_product_ui_product_form_submit($form, &$form_state) {
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

  if ($form_state['clicked_button']['#parents'][0] == 'save_continue') {
    $form_state['redirect'] = 'admin/commerce/products/add/' . $product->type;
  }
  else {
    $form_state['redirect'] = 'admin/commerce/products';
  }
}

/**
 * Confirmation form for deleting a product.
 */
function commerce_product_ui_product_delete_form($form, &$form_state, $product) {
  $form_state['product'] = $product;

  // TODO: Replace the SKU in the description parameter with the product view
  // per http://drupal.org/node/733044.

  $form = confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $product->title)),
    'admin/commerce/products',
    '<p>' . t('SKU: @sku. Deleting this product cannot be undone.', array('@sku' => $product->sku)) . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_product_ui_product_delete_form().
 */
function commerce_product_ui_product_delete_form_submit($form, &$form_state) {
  $product = $form_state['product'];

  if (commerce_product_delete($product->product_id)) {
    drupal_set_message(t('%title has been deleted.', array('%title' => $product->title)));
    watchdog('commerce_product', 'Deleted product %title (SKU: @sku).', array('%title' => $product->title, '@sku' => $product->sku), WATCHDOG_NOTICE);
  }

  $form_state['redirect'] = 'admin/commerce/products';
}
