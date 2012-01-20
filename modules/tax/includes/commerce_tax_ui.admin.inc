<?php

/**
 * @file
 * Administrative callbacks and form builder functions for the Tax UI module.
 */


/**
 * Displays an overview of Tax UI defined tax rates.
 */
function commerce_tax_ui_overview($type) {
  drupal_add_css(drupal_get_path('module', 'commerce_tax') . '/theme/commerce_tax.admin.css');

  // Load the items that will be represented in the overview table.
  if ($type == 'rates') {
    $items = commerce_tax_rates();
  }
  else {
    $items = commerce_tax_types();
  }

  $header = array(
    t('Name'),
    t('Operations'),
  );

  $rows = array();

  // Loop through all of the items to include in the overview.
  foreach ($items as $name => $item) {
    if ($item['admin_list']) {
      // Build the operation links for the current item.
      $links = menu_contextual_links('commerce-tax-' . $type, 'admin/commerce/config/taxes/' . $type, array(strtr($name, '_', '-')));

      // Add the item's row to the table's rows array.
      $rows[] = array(
        ($type == 'rates') ? theme('tax_rate_admin_overview', array('tax_rate' => $item)) : theme('tax_type_admin_overview', array('tax_type' => $item)),
        theme('links', array('links' => $links, 'attributes' => array('class' => 'links inline operations'))),
      );
    }
  }

  // If no items are defined...
  if (empty($rows)) {
    // Add a standard empty row with a link to add a new item.
    if ($type == 'rates') {
      $empty_text = t('There are no tax rates yet. <a href="@link">Add a tax rate</a>.', array('@link' => url('admin/commerce/config/taxes/rates/add')));
    }
    else {
      $empty_text = t('There are no tax types yet. <a href="@link">Add a tax type</a>.', array('@link' => url('admin/commerce/config/taxes/types/add')));
    }

    $rows[] = array(
      array(
        'data' => $empty_text,
        'colspan' => 2,
      )
    );
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Builds an overview of a tax rate for display to an administrator.
 *
 * @param $variables
 *   An array of variables used to generate the display; by default includes the
 *     tax rate key with a value of the tax rate array.
 *
 * @ingroup themeable
 */
function theme_tax_rate_admin_overview($variables) {
  $tax_rate = $variables['tax_rate'];

  // Build the description as an array so we can skip the actual tax rate
  // description if it isn't set and just include the rate.
  $description = array();

  if (!empty($tax_rate['description'])) {
    $description[] = filter_xss_admin($tax_rate['description']);
  }

  $description[] = t('Rate: @rate', array('@rate' => $tax_rate['rate']));

  // Build the actual output.
  $output = check_plain($tax_rate['title']);
  $output .= ' <small>' . t('(Machine name: @type)', array('@type' => $tax_rate['name'])) . '</small>';
  $output .= '<div class="description">' . implode('<br />', $description) . '</div>';

  return $output;
}

/**
 * Builds an overview of a tax type for display to an administrator.
 *
 * @param $variables
 *   An array of variables used to generate the display; by default includes the
 *     tax type key with a value of the tax type array.
 *
 * @ingroup themeable
 */
function theme_tax_type_admin_overview($variables) {
  $tax_type = $variables['tax_type'];

  $output = check_plain($tax_type['title']);
  $output .= ' <small>' . t('(Machine name: @type)', array('@type' => $tax_type['name'])) . '</small>';
  $output .= '<div class="description">' . filter_xss_admin($tax_type['description']) . '</div>';

  return $output;
}

/**
 * Form callback wrapper: ensure tax types exist before going to the add form.
 */
function commerce_tax_ui_tax_rate_add_form_wrapper($tax_rate) {
  if (count(commerce_tax_type_titles()) == 0) {
    drupal_set_message(t('You must define at least one tax type before you can start adding tax rates.'), 'error');
    drupal_goto('admin/commerce/config/taxes/types');
  }

  return drupal_get_form('commerce_tax_ui_tax_rate_form', $tax_rate);
}

/**
 * Form callback: create or edit a tax rate.
 *
 * @param $tax_rate
 *   The tax rate array to edit or for a create form an empty tax rate
 *     array with properties instantiated but not populated.
 */
function commerce_tax_ui_tax_rate_form($form, &$form_state, $tax_rate) {
  // Store the initial tax rate in the form state.
  $form_state['tax_rate'] = $tax_rate;

  $form['tax_rate'] = array(
    '#tree' => TRUE,
  );

  $form['tax_rate']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $tax_rate['title'],
    '#description' => t('The administrative title of this tax rate. It is recommended that this title begin with a capital letter and contain only letters, numbers, and spaces.'),
    '#required' => TRUE,
    '#size' => 32,
    '#field_suffix' => ' <small id="edit-tax-rate-title-suffix">' . t('Machine name: @name', array('@name' => $tax_rate['name'])) . '</small>',
  );

  if (empty($tax_rate['name'])) {
    $form['tax_rate']['name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#default_value' => $tax_rate['name'],
      '#maxlength' => 64,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => 'commerce_tax_rate_load',
        'source' => array('tax_rate', 'title'),
      ),
      '#description' => t('The machine-name of this tax rate. This name must contain only lowercase letters, numbers, and underscores. It must be unique.'),
    );
  }

  $form['tax_rate']['display_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Display title'),
    '#default_value' => $tax_rate['display_title'],
    '#description' => t('The front end display title of this tax rate shown to customers. Leave blank to default to the <em>Title</em> from above.'),
    '#size' => 32,
  );

  $form['tax_rate']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#description' => t('Describe this tax rate if necessary. The text will be displayed in the tax rate overview table.'),
    '#default_value' => $tax_rate['description'],
    '#rows' => 3,
  );

  $form['tax_rate']['rate'] = array(
    '#type' => 'textfield',
    '#title' => t('Rate'),
    '#description' => t('The percentage used to calculate this tax expressed as a decimal, e.g. .06 for a rate of 6%.'),
    '#default_value' => $tax_rate['rate'],
    '#size' => 16,
    '#required' => TRUE,
  );

  $form['tax_rate']['type'] = array(
    '#type' => 'select',
    '#title' => t('Type'),
    '#description' => t('The tax type for this rate.'),
    '#options' => commerce_tax_type_titles(),
    '#default_value' => $tax_rate['type'],
    '#required' => TRUE,
  );

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 40,
  );

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save tax rate'),
  );

  if (!empty($form_state['tax_rate']['name'])) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete tax rate'),
      '#suffix' => l(t('Cancel'), 'admin/commerce/config/taxes'),
      '#submit' => array('commerce_tax_ui_tax_rate_form_delete_submit'),
      '#weight' => 45,
    );
  }
  else {
    $form['actions']['submit']['#suffix'] = l(t('Cancel'), 'admin/commerce/config/taxes');
  }

  return $form;
}

/**
 * Validation callback for commerce_tax_ui_tax_rate_form().
 */
function commerce_tax_ui_tax_rate_form_validate($form, &$form_state) {
  $tax_rate = $form_state['tax_rate'];

  // If saving a new tax rate, ensure it has a unique machine name.
  if (empty($tax_rate['name'])) {
    if (commerce_tax_rate_load($form_state['values']['tax_rate']['name']) != FALSE) {
      form_set_error('tax_rate][name', t('The machine-name specified is already in use.'));
    }
  }

  // Validate the data type of the tax rate.
  if (!is_numeric($form_state['values']['tax_rate']['rate'])) {
    form_set_error('tax_rate][rate', t('You must specify a numeric rate value.'));
  }
}

/**
 * Form submit handler: save a tax rate.
 */
function commerce_tax_ui_tax_rate_form_submit($form, &$form_state) {
  $tax_rate = $form_state['tax_rate'];
  $updated = !empty($tax_rate['name']);

  foreach ($form_state['values']['tax_rate'] as $key => $value) {
    $tax_rate[$key] = $value;
  }

  // Default the display title to the title if empty.
  if (empty($tax_rate['display_title'])) {
    $tax_rate['display_title'] = $tax_rate['title'];
  }

  // Write the tax rate to the database.
  $tax_rate['is_new'] = !$updated;
  commerce_tax_ui_tax_rate_save($tax_rate);

  // Redirect based on the button clicked.
  drupal_set_message(t('Tax rate saved.'));

  $form_state['redirect'] = 'admin/commerce/config/taxes';
}

/**
 * Submit callback for delete button on commerce_tax_ui_tax_rate_form().
 *
 * @see commerce_tax_ui_tax_rate_form()
 */
function commerce_tax_ui_tax_rate_form_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/commerce/config/taxes/rates/' . strtr($form_state['tax_rate']['name'], '_', '-') . '/delete';
}

/**
 * Form callback: confirmation form for deleting a tax rate.
 *
 * @param $tax_rate
 *   The tax rate array to be deleted.
 *
 * @see confirm_form()
 */
function commerce_tax_ui_tax_rate_delete_form($form, &$form_state, $tax_rate) {
  $form_state['tax_rate'] = $tax_rate;

  $form = confirm_form($form,
    t('Are you sure you want to delete the %title tax rate?', array('%title' => $tax_rate['title'])),
    'admin/commerce/config/taxes',
    '<p>' . t('This action cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_tax_ui_tax_rate_delete_form().
 */
function commerce_tax_ui_tax_rate_delete_form_submit($form, &$form_state) {
  $tax_rate = $form_state['tax_rate'];

  commerce_tax_ui_tax_rate_delete($tax_rate['name']);

  drupal_set_message(t('The tax rate %title has been deleted.', array('%title' => $tax_rate['title'])));
  watchdog('commerce_tax', 'Deleted tax rate %title.', array('%title' => $tax_rate['title']), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/commerce/config/taxes';
}

/**
 * Form callback: create or edit a tax type.
 *
 * @param $tax_type
 *   The tax type array to edit or for a create form an empty tax type
 *     array with properties instantiated but not populated.
 */
function commerce_tax_ui_tax_type_form($form, &$form_state, $tax_type) {
  // Store the initial tax type in the form state.
  $form_state['tax_type'] = $tax_type;

  $form['tax_type'] = array(
    '#tree' => TRUE,
  );

  $form['tax_type']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $tax_type['title'],
    '#description' => t('The administrative title of this tax type. It is recommended that this title begin with a capital letter and contain only letters, numbers, and spaces.'),
    '#required' => TRUE,
    '#size' => 32,
    '#field_suffix' => ' <small id="edit-tax-rate-title-suffix">' . t('Machine name: @name', array('@name' => $tax_type['name'])) . '</small>',
  );

  if (empty($tax_type['name'])) {
    $form['tax_type']['name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#default_value' => $tax_type['name'],
      '#maxlength' => 64,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => 'commerce_tax_type_load',
        'source' => array('tax_type', 'title'),
      ),
      '#description' => t('The machine-name of this tax type. This name must contain only lowercase letters, numbers, and underscores. It must be unique.'),
    );
  }

  $form['tax_type']['display_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Display title'),
    '#default_value' => $tax_type['display_title'],
    '#description' => t('The front end display title of this tax type shown to customers. Leave blank to default to the <em>Title</em> from above.'),
    '#size' => 32,
  );

  $form['tax_type']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#description' => t('Describe this tax type if necessary. The text will be displayed in the tax type overview table.'),
    '#default_value' => $tax_type['description'],
    '#rows' => 3,
  );

  $form['tax_type']['display_inclusive'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display taxes of this type inclusive in product prices.'),
    '#default_value' => $tax_type['display_inclusive'],
  );

  $form['tax_type']['round_mode'] = array(
    '#type' => 'radios',
    '#title' => t('Tax amount rounding mode'),
    '#description' => t('Specify what type of rounding should occur when tax rates of this type are calculated for the unit price of a line item. Sales taxes will generally not round these amounts at the unit price level, while VAT style taxes will generally round the half up.'),
    '#options' => commerce_round_mode_options_list(),
    '#default_value' => $tax_type['round_mode'],
  );

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 40,
  );

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save tax type'),
  );

  if (!empty($form_state['tax_type']['name'])) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete tax type'),
      '#suffix' => l(t('Cancel'), 'admin/commerce/config/taxes/types'),
      '#submit' => array('commerce_tax_ui_tax_type_form_delete_submit'),
      '#weight' => 45,
    );
  }
  else {
    $form['actions']['submit']['#suffix'] = l(t('Cancel'), 'admin/commerce/config/taxes/types');
  }

  return $form;
}

/**
 * Validation callback for commerce_tax_ui_tax_type_form().
 */
function commerce_tax_ui_tax_type_form_validate($form, &$form_state) {
  $tax_type = $form_state['tax_type'];

  // If saving a new tax type, ensure it has a unique machine name.
  if (empty($tax_type['name'])) {
    if (commerce_tax_type_load($form_state['values']['tax_type']['name']) != FALSE) {
      form_set_error('tax_type][name', t('The machine-name specified is already in use.'));
    }
  }
}

/**
 * Form submit handler: save a tax type.
 */
function commerce_tax_ui_tax_type_form_submit($form, &$form_state) {
  $tax_type = $form_state['tax_type'];
  $updated = !empty($tax_type['name']);

  foreach ($form_state['values']['tax_type'] as $key => $value) {
    $tax_type[$key] = $value;
  }

  // Default the display title to the title if empty.
  if (empty($tax_type['display_title'])) {
    $tax_type['display_title'] = $tax_type['title'];
  }

  // Write the tax type to the database.
  $tax_type['is_new'] = !$updated;
  commerce_tax_ui_tax_type_save($tax_type);

  // Clear the field cache if the round mode changed.
  if ($form_state['tax_type']['round_mode'] != $tax_type['round_mode']) {
    field_cache_clear();
  }

  // Redirect based on the button clicked.
  drupal_set_message(t('Tax type saved.'));

  $form_state['redirect'] = 'admin/commerce/config/taxes/types';
}

/**
 * Submit callback for delete button on commerce_tax_ui_tax_type_form().
 *
 * @see commerce_tax_ui_tax_type_form()
 */
function commerce_tax_ui_tax_type_form_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/commerce/config/taxes/types/' . strtr($form_state['tax_type']['name'], '_', '-') . '/delete';
}

/**
 * Form callback wrapper: confirmation form for deleting a tax type.
 *
 * @param $tax_type
 *   The tax type array being deleted by this form.
 *
 * @see commerce_tax_ui_tax_type_delete_form()
 */
function commerce_tax_ui_tax_type_delete_form_wrapper($tax_type) {
  // Return a message if the taxtype is not governed by Tax UI.
  if ($tax_type['module'] != 'commerce_tax_ui') {
    return t('This tax type cannot be deleted, because it is not defined by the Tax UI module.');
  }

  // Don't allow deletion of tax types that have tax rates.
  $query = db_select('commerce_tax_rate')
    ->condition('type', $tax_type['name'])
    ->countQuery()
    ->execute();

  $count = $query->fetchField();

  if ($count > 0) {
    drupal_set_title(t('Cannot delete the %title tax type', array('%title' => $tax_type['title'])), PASS_THROUGH);

    return format_plural($count,
      'There is a tax rate of this type. It cannot be deleted.',
      'There are @count tax rates of this type. It cannot be deleted.'
    );
  }

  return drupal_get_form('commerce_tax_ui_tax_type_delete_form', $tax_type);
}

/**
 * Form callback: confirmation form for deleting a tax type.
 *
 * @param $tax_type
 *   The tax type array to be deleted.
 *
 * @see confirm_form()
 */
function commerce_tax_ui_tax_type_delete_form($form, &$form_state, $tax_type) {
  $form_state['tax_type'] = $tax_type;

  $form = confirm_form($form,
    t('Are you sure you want to delete the %title tax type?', array('%title' => $tax_type['title'])),
    'admin/commerce/config/taxes/types',
    '<p>' . t('This action cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );

  return $form;
}

/**
 * Submit callback for commerce_tax_ui_tax_type_delete_form().
 */
function commerce_tax_ui_tax_type_delete_form_submit($form, &$form_state) {
  $tax_type = $form_state['tax_type'];

  commerce_tax_ui_tax_type_delete($tax_type['name']);

  drupal_set_message(t('The tax type %title has been deleted.', array('%title' => $tax_type['title'])));
  watchdog('commerce_tax', 'Deleted tax type %title.', array('%title' => $tax_type['title']), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/commerce/config/taxes/types';
}
