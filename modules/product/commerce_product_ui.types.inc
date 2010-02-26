<?php
// $Id$

/**
 * Menu callback; display an overview of available types.
 */
function commerce_product_ui_types_overview() {
  $header = array(
    t('Name'),
    t('Operations'),
  );
  $rows = array();
  foreach (commerce_product_types() as $type) {
    $links = menu_contextual_links('commerce-product-type', 'admin/commerce/products/types', array($type['product_type']));
    $rows[] = array(
      $type['name'],
      theme('links', array('links' => $links)),
    );
  }
  if (empty($rows)) {
    $rows[] = array(
      array(
        'data' => t('There are no product types yet. <a href="@link">Add a product type</a>.', array('@link' => url('admin/commerce/products/types/add'))),
        'colspan' => 2,
      )
    );
  }
  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Form callback: create or edit a product type.
 */
function commerce_product_ui_type_form($form, &$form_state, $product_type) {
  // Store the initial product type in the form state.
  $form_state['product_type'] = $product_type;

  $form['product_type'] = array(
    '#tree' => TRUE,
  );

  $form['product_type']['name'] = array(
    '#title' => t('Name'),
    '#type' => 'textfield',
    '#default_value' => $product_type->name,
    '#description' => t('The human-readable name of this product type.'),
    '#required' => TRUE,
    '#size' => 32,
    '#field_suffix' => ' <small id="edit-product-type-name-suffix">' . $product_type->product_type . '</small>',
  );

  if (empty($product_type->product_type)) {
    $js_settings = array(
      'type' => 'setting',
      'data' => array(
        'machineReadableValue' => array(
          'product-type-name' => array(
            'text' => t('Machine name'),
            'target' => 'product-type-product-type',
            'searchPattern' => '[^a-z0-9]+',
            'replaceToken' => '_',
          ),
        ),
      ),
    );
    $form['product_type']['product_type'] = array(
      '#title' => t('Machine name'),
      '#type' => 'textfield',
      '#default_value' => $product_type->product_type,
      '#maxlength' => 32,
      '#required' => TRUE,
      '#description' => t('The machine-readable name of this product type. This name must contain only lowercase letters, numbers, and underscores, it must be unique.'),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'system') . '/system.js', $js_settings),
      ),
    );
  }

  $form['product_type']['description'] = array(
    '#title' => t('Description'),
    '#type' => 'textarea',
    '#default_value' => $product_type->description,
    '#rows' => 3,
    '#description' => t('The description of this product type.'),
  );

  $form['buttons'] = array();
  $form['buttons']['#weight'] = 100;
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  if (!empty($product_type->product_type)) {
    $form['buttons']['submit']['#suffix'] = t(' or <a href="@url">delete this product type</a>.', array('@url' => url('admin/commerce/products/types/' . $product_type->product_type . '/delete')));
  }

  return $form;
}

/**
 * Form submit handler: save a product type.
 */
function commerce_product_ui_type_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];
  $updated = !empty($product_type->product_type);

  foreach ($form_state['values']['product_type'] as $key => $value) {
    $product_type->$key = $value;
  }

  // Write the product type to the database.
  $product_type->is_new = !$updated;
  commerce_product_type_save($product_type);

  drupal_set_message(t('Product type saved.'));
  $form_state['redirect'] = 'admin/commerce/products/types';
}

/**
 * Confirmation form for deleting a product type.
 */
function commerce_product_ui_type_delete_form($form, &$form_state, $product_type) {
  $form_state['product_type'] = $product_type;

  $form = confirm_form($form,
    t('Are you sure you want to delete the product type %name?', array('%name' => $product_type->name)),
    'admin/commerce/products/types',
    t('If you have any content left in this field, it will be lost. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

function commerce_product_ui_type_delete_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];

  commerce_product_type_delete($product_type->product_type);

  drupal_set_message(t('The product type %name has been deleted.', array('%name' => $product_type->name)));
  watchdog('commerce_product', 'Deleted product type %name.', array('%name' => $product_type->name), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/commerce/products/types';
}
