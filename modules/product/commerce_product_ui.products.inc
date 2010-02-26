<?php
// $Id$

/**
 * Form callback: create or edit a product.
 */
function commerce_product_ui_product_form($form, &$form_state, $product) {
  $form = array();

  // Add the default field elements.
  $form['product_sku'] = array(
    '#type' => 'textfield',
    '#title' => t('Product SKU'),
    '#description' => t('Supply a unique human readable identifier for this product.'),
    '#default_value' => isset($product->product_sku) ? $product->product_sku : '',
    '#required' => TRUE,
    '#weight' => -10,
  );

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#description' => t('Supply a title for administrative display.'),
    '#default_value' => isset($product->title) ? $product->title : '',
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
  // Ensure the SKU entered is unique.
  if ($product_id = db_query('SELECT product_id FROM {commerce_product} WHERE product_sku = :product_sku', array(':product_sku' => $form_state['values']['product_sku']))->fetchField()) {
    if (empty($form_state['commerce_product']->product_id) || $product_id != $form_state['commerce_product']->product_id) {
      form_set_error('product_sku', 'This SKU is already in use and must be unique. Please supply another value.');
    }
  }

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
  $product->product_sku = $form_state['values']['product_sku'];
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
    $form_state['redirect'] = 'admin/commerce/products/add/' . $product->product_type;
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
    '<p>' . t('SKU: @sku. Deleting this product cannot be undone.', array('@sku' => $product->product_sku)) . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

function commerce_product_ui_product_delete_form_submit($form, &$form_state) {
  $product = $form_state['product'];

  if (commerce_product_delete($product->product_id)) {
    drupal_set_message(t('%title has been deleted.', array('%title' => $product->title)));
    watchdog('commerce_product', 'Deleted product %title (SKU: @sku).', array('%title' => $product->title, '@sku' => $product->product_sku), WATCHDOG_NOTICE);
  }

  $form_state['redirect'] = 'admin/commerce/products';
}
