<?php
// $Id$

/**
 * Page callback: create a new product.
 */
function commerce_product_ui_create($type) {
  $product_stub = (object) array(
    'product_type' => $type,
  );
  return drupal_get_form('commerce_product_ui_form', $product_stub);
}

/**
 * Form callback: create or edit a product.
 */
function commerce_product_ui_form($form, &$form_state, $product) {
  // Attach default parameters.
  $form['product_sku'] = array(
    '#type' => 'textfield',
    '#title' => t('Product SKU'),
    '#default_value' => isset($product->product_sku) ? $product->product_sku : '',
    '#required' => TRUE,
    '#weight' => -1,
  );

  // Attach fields.
  $form_state['commerce_product'] = $product;
  field_attach_form('commerce_product', $product, &$form, &$form_state);

  $form['buttons'] = array();
  $form['buttons']['#weight'] = 100;
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Validation callback for commerce_product_ui_form().
 */
function commerce_product_ui_form_validate($form, &$form_state) {
  // Notify field widgets.
  field_attach_form_validate('commerce_product', $form_state['commerce_product'], $form, $form_state);
}

/**
 * Submit callback for commerce_product_ui_form().
 */
function commerce_product_ui_form_submit($form, &$form_state) {
  $product = &$form_state['commerce_product'];

  // Save default parametesr back into the $product object.
  $product->product_sku = $form_state['values']['product_sku'];

  // Notify field widgets.
  field_attach_submit('commerce_product', $product, $form, $form_state);
  debug($product);

  // Save the product.
  commerce_product_save($product);

  // Redirect.
  drupal_set_message(t('Saved product.'));
  $form_state['redirect'] = 'admin/commerce/products';
}
