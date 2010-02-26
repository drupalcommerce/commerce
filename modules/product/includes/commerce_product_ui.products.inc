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
  $form['sku'] = array(
    '#type' => 'textfield',
    '#title' => t('Product SKU'),
    '#description' => t('Supply a unique human readable identifier for this product. Accepts letters, numbers, dashes, underscores, and the product tokens [product:product-id] and [product:type].'),
    '#default_value' => isset($product->sku) ? $product->sku : '',
    '#maxlength' => 128,
    '#required' => TRUE,
    '#weight' => -10,
  );

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#description' => t('Supply a title for administrative display.'),
    '#default_value' => isset($product->title) ? $product->title : '',
    '#maxlength' => 255,
    '#required' => TRUE,
    '#weight' => -5,
  );

  // Add the field related form elements.
  $form_state['commerce_product'] = $product;
  field_attach_form('commerce_product', $product, &$form, &$form_state);

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save product'),
    '#weight' => 40,
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
  // Ensure that only valid tokens were used.
  $valid_tokens = TRUE;

  foreach (token_scan($form_state['values']['sku']) as $type => $token) {
    if ($type !== 'product') {
      $valid_tokens = FALSE;
    }
    else {
      foreach (array_keys($token) as $value) {
        if (!in_array($value, array('product-id', 'type'))) {
          $valid_tokens = FALSE;
        }
      }
    }
  }

  // Register the error if an invalid token was detected.
  if (!$valid_tokens) {
    form_set_error('sku', 'Product SKU contains invalid tokens.');
  }

  // Check the remaining characters of the string for validity by doing a test
  // replacement of the tokens.
  // TODO: Perform a regex match for alphanumeric, hyphen, and underscore
  // characters once the tokens have been stripped out.

  // Ensure the SKU entered is unique.
  if ($product_id = db_query('SELECT product_id FROM {commerce_product} WHERE sku = :sku', array(':sku' => $form_state['values']['sku']))->fetchField()) {
    if (empty($form_state['commerce_product']->product_id) || $product_id != $form_state['commerce_product']->product_id) {
      form_set_error('sku', 'This SKU is already in use and must be unique. Please supply another value.');
    }
  }

  // Ensure the SKU is alphanumeric with only hyphens and underscores.
  // TODO: Make this a validation API function in commerce_product.module.

  // Notify field widgets.
  field_attach_form_validate('commerce_product', $form_state['commerce_product'], $form, $form_state);
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

  // Set the product's uid if it's being created at this time.
  if (empty($product->product_id)) {
    $product->uid = $user->uid;
  }

  // Notify field widgets.
  field_attach_submit('commerce_product', $product, $form, $form_state);
  // debug($product);

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

  // TODO: Replace the SKU in the description parameter with the product view.

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
