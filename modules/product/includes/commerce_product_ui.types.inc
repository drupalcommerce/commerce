<?php
// $Id$

/**
 * Menu callback: display an overview of available types.
 */
function commerce_product_ui_types_overview() {
  drupal_add_css(drupal_get_path('module', 'commerce_product_ui') .'/theme/commerce_product_ui.types.css');

  $header = array(
    t('Name'),
    t('Operations'),
  );

  $rows = array();

  // Loop through all defined product types.
  foreach (commerce_product_types() as $product_type) {
    // Build the operation links for the current product type.
    $links = menu_contextual_links('commerce-product-type', 'admin/commerce/products/types', array($product_type['type']));

    // Add the product type's row to the table's rows array.
    $rows[] = array(
      theme('product_type_admin_overview', array('product_type' => $product_type)),
      theme('links', array('links' => $links, 'attributes' => array('class' => 'links operations'))),
    );
  }

  // If no product types are defined...
  if (empty($rows)) {
    // Add a standard empty row with a link to add a new product type.
    $rows[] = array(
      array(
        'data' => t('There are no product types yet. <a href="@link">Add product type</a>.', array('@link' => url('admin/commerce/products/types/add'))),
        'colspan' => 2,
      )
    );
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Builds an overview of a product type for display to an administrator.
 *
 * @param $variables
 *   An array of variables used to generate the display; by default includes the
 *     type key with a value of the product type object.
 *
 * @ingroup themeable
 */
function theme_product_type_admin_overview($variables) {
  $product_type = $variables['product_type'];

  $output = check_plain($product_type['name']);
  $output .= ' <small> (Machine name: ' . check_plain($product_type['type']) . ')</small>';
  $output .= '<div class="description">' . filter_xss_admin($product_type['description']) . '</div>';

  return $output;
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
      '#title' => t('Machine name'),
      '#type' => 'textfield',
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
    '#title' => t('Description'),
    '#type' => 'textarea',
    '#default_value' => $product_type->description,
    '#rows' => 3,
    '#description' => t('Describe this product type. The text will be displayed on the <em>Add new content</em> page.'),
  );

  // TODO: Add a help textarea and display that on the product add form.

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save product type'),
    '#weight' => 40,
  );

  if (!empty($product_type->type)) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete product type'),
      '#suffix' => l('Cancel', 'admin/commerce/products/types'),
      '#weight' => 45,
    );
  }
  else {
    $form['actions']['save_continue'] = array(
      '#type' => 'submit',
      '#value' => t('Save and add fields'),
      '#suffix' => l('Cancel', 'admin/commerce/products/types'),
      '#weight' => 45,
    );
  }

  return $form;
}

/**
 * Form submit handler: save a product type.
 */
function commerce_product_ui_type_form_submit($form, &$form_state) {
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

  if ($form_state['clicked_button']['#parents'][0] == 'save_continue') {
    $form_state['redirect'] = 'admin/commerce/products/types/' . $product_type->type . '/fields';
  }
  else {
    $form_state['redirect'] = 'admin/commerce/products/types';
  }
}

/**
 * Confirmation form for deleting a product type.
 */
function commerce_product_ui_type_delete_form($form, &$form_state, $product_type) {
  $form_state['product_type'] = $product_type;

  $form = confirm_form($form,
    t('Are you sure you want to delete the %name product type?', array('%name' => $product_type->name)),
    'admin/commerce/products/types',
    '<p>' . t('This action cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

function commerce_product_ui_type_delete_form_submit($form, &$form_state) {
  $product_type = $form_state['product_type'];

  commerce_product_type_delete($product_type->type);

  drupal_set_message(t('The product type %name has been deleted.', array('%name' => $product_type->name)));
  watchdog('commerce_product', 'Deleted product type %name.', array('%name' => $product_type->name), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/commerce/products/types';
}
