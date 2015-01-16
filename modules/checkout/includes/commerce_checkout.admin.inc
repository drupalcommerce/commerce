<?php

/**
 * @file
 * Administrative callbacks for the Checkout module.
 */


/**
 * Build the checkout form builder, adding in data for the checkout pages and
 *   the appropriate fields to enable tabledrag on the checkout panes.
 */
function commerce_checkout_builder_form($form, &$form_state) {
  // Load an array of all available checkout pages.
  $checkout_pages = commerce_checkout_pages();

  // Add a "disabled" pseudo-page.
  $checkout_pages['disabled'] = array('page_id' => 'disabled', 'name' => t('Disabled'));

  $form['checkout_pages'] = array(
    '#type' => 'value',
    '#value' => $checkout_pages,
  );

  $checkout_pages_options = array();

  // Create arrays for checkout panes in each of the pages.
  foreach (array_keys($checkout_pages) as $page_id) {
    $form['page'][$page_id]['panes'] = array('#tree' => TRUE);

    // Build the options list for selecting the pane's checkout page.
    $checkout_pages_options[$page_id] = $checkout_pages[$page_id]['name'];
  }

  // Loop through all the checkout panes on the site.
  $panes = commerce_checkout_panes();

  foreach ($panes as $pane_id => $checkout_pane) {
    // Determine a checkout pane's current checkout page.
    $page_id = $checkout_pane['enabled'] ? $checkout_pane['page'] : 'disabled';

    // If the page no longer exists, place the pane on the first page.
    if (empty($checkout_pages[$page_id])) {
      reset($checkout_pages);
      $page_id = key($checkout_pages);
    }

    // Add the pane's name to the form array.
    $form['page'][$page_id]['panes'][$pane_id]['name'] = array(
      '#markup' => check_plain($checkout_pane['name']),
    );

    // Add the select field for the pane's checkout page.
    $form['page'][$page_id]['panes'][$pane_id]['page'] = array(
      '#type' => 'select',
      '#options' => $checkout_pages_options,
      '#default_value' => $checkout_pane['page'],
      '#attributes' => array('class' => array('checkout-pane-page checkout-pane-page-'. $checkout_pane['page'])),
    );

    // Add the select field for the pane's weight.
    $form['page'][$page_id]['panes'][$pane_id]['weight'] = array(
      '#type' => 'weight',
      '#delta' => 20,
      '#default_value' => $checkout_pane['weight'],
      '#attributes' => array('class' => array('checkout-pane-weight checkout-pane-weight-'. $checkout_pane['page'])),
    );

    // Add a configuration link for the pane.
    $form['page'][$page_id]['panes'][$pane_id]['ops'] = array(
      '#markup' => l(t('configure'), 'admin/commerce/config/checkout/form/pane/'. $pane_id),
    );
  }

  $form['actions'] = array(
    '#type' => 'actions',
    '#tree' => FALSE,
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
    '#submit' => array('commerce_checkout_builder_form_save_submit'),
  );
  $form['actions']['reset'] = array(
    '#type' => 'submit',
    '#value' => t('Reset to defaults'),
    '#submit' => array('commerce_checkout_builder_form_reset_submit'),
  );

  return $form;
}

/**
 * Validation handler for the checkout builder form.
 */
function commerce_checkout_builder_form_validate(&$form, &$form_state) {
  $pages = commerce_checkout_pages();

  // Get unmodified checkout panes.
  $panes = commerce_checkout_panes(array(), TRUE);

  // Get saved checkout panes. If an error is detected, we'll need it to
  // reference the original form structure so we can accurately locate the
  // elements in the form and set their values.
  $saved_panes = commerce_checkout_panes();

  // Loop through each configured panes.
  foreach ($form_state['values']['panes'] as $pane_id => $checkout_pane) {
    // Check if pane should be locked but is configured on the wrong checkout page.
    if (!empty($panes[$pane_id]['locked']) && $checkout_pane['page'] != $panes[$pane_id]['page']) {
      $element = &$form['page'][$saved_panes[$pane_id]['page']]['panes'][$pane_id]['page'];

      if ($panes[$pane_id]['page'] == 'disabled') {
        drupal_set_message(t('%pane must be configured before it can be enabled. It has been moved back to a disabled position.', array('%pane' => $panes[$pane_id]['title'])), 'warning');
      }
      else {
        drupal_set_message(t('%pane is locked in the code to the %page page and was repositioned accordingly.', array('%pane' => $panes[$pane_id]['title'], '%page' => $pages[$panes[$pane_id]['page']]['name'])), 'warning');
      }

      form_set_value($element, $panes[$pane_id]['page'], $form_state);
    }
  }
}

/**
 * Submit handler for the checkout builder form's save button.
 */
function commerce_checkout_builder_form_save_submit($form, &$form_state) {
  // Loop through each of the checkout panes.
  if (!empty($form_state['values']['panes'])) {
    foreach ($form_state['values']['panes'] as $pane_id => $data) {
      // Load and update the checkout pane array.
      $checkout_pane = commerce_checkout_pane_load($pane_id);
      $checkout_pane['weight'] = $data['weight'];

      // Accommodate the "Disabled" pseudo-page in the form.
      if ($data['page'] == 'disabled') {
        $checkout_pane['enabled'] = FALSE;
        $checkout_pane['page'] = 'disabled';
      }
      else {
        $checkout_pane['enabled'] = TRUE;
        $checkout_pane['page'] = $data['page'];
      }

      commerce_checkout_pane_save($checkout_pane);
    }
  }

  drupal_set_message(t('Checkout pane positions saved.'));
}

/**
 * Submit handler for the checkout builder form's reset button.
 */
function commerce_checkout_builder_form_reset_submit($form, &$form_state) {
  // Empty the checkout pane table of configuration data.
  db_truncate('commerce_checkout_pane')->execute();
  drupal_set_message(t('Checkout pane positions reset.'));
}

/**
 * Theme the checkout pages form to enable tabledrag.
 */
function theme_commerce_checkout_builder_form($variables) {
  $form = $variables['form'];

  drupal_add_css(drupal_get_path('module', 'commerce_checkout') .'/theme/commerce_checkout.admin.css');
  drupal_add_js(drupal_get_path('module', 'commerce_checkout') .'/commerce_checkout_admin.js');

  // Build the full table header; Page and Weight will be hidden by tabledrag.
  $header = array(t('Checkout pane'), t('Page'), t('Weight'), t('Operations'));

  $rows = array();

  // Loop through each page array in the form.
  foreach ($form['checkout_pages']['#value'] as $page_id => $checkout_page) {
    // Initialize the tabledrag for this page.
    drupal_add_tabledrag('panes', 'match', 'sibling', 'checkout-pane-page', 'checkout-pane-page-'. $page_id);
    drupal_add_tabledrag('panes', 'order', 'sibling', 'checkout-pane-weight', 'checkout-pane-weight-'. $page_id);

    // Add a non-draggable header row for each checkout page.
    $row = array(
      array('data' => $checkout_page['name'], 'colspan' => 4),
    );

    $rows[] = array('data' => $row, 'class' => array('page-header'));

    // Determine whether the page currently has any panes in it.
    $class = count(element_children($form['page'][$page_id]['panes'])) == 0 ? 'page-empty' : 'page-populated';

    // Add a row to the table that will be automatically shown or hidden as a
    // placeholder for pages that do not have any panes.
    $rows[] = array(
      'data' => array(
        array('data' => $page_id != 'disabled' ? t('No panes on this page.') : t('No disabled panes.'), 'colspan' => 4),
      ),
      'class' => array('page-message page-'. $page_id .'-message', $class),
    );

    // Loop through each checkout pane currently assigned to this page.
    foreach (element_children($form['page'][$page_id]['panes']) as $pane_id) {
      $row = array(
        drupal_render($form['page'][$page_id]['panes'][$pane_id]['name']),
        drupal_render($form['page'][$page_id]['panes'][$pane_id]['page']),
        drupal_render($form['page'][$page_id]['panes'][$pane_id]['weight']),
        drupal_render($form['page'][$page_id]['panes'][$pane_id]['ops']),
      );

      $rows[] = array('data' => $row, 'class' => array('draggable'));
    }
  }

  $variables = array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'panes'),
  );

  return theme('table', $variables) . drupal_render_children($form);
}

/**
 * Build the configuration form for a checkout pane.
 */
function commerce_checkout_pane_settings_form($form, &$form_state, $checkout_pane) {
  // Build the form array with the bare minimum fields.
  $form['checkout_pane'] = array(
    '#type' => 'value',
    '#value' => $checkout_pane,
  );

  $form['display'] = array(
    '#type' => 'fieldset',
    '#title' => t('Display settings'),
    '#description' => t('These settings are common to all checkout panes and affect their appearance on the checkout form.'),
  );
  $form['display']['collapsibility'] = array(
    '#type' => 'radios',
    '#title' => t('Checkout form fieldset display'),
    '#description' => t('Checkout panes are rendered on the checkout form in individual fieldsets.') .'<br />'. t('Specify here how the fieldset for this pane will appear.'),
    '#options' => array(
      'default' => t('Display this pane in a non-collapsible fieldset.'),
      'collapsible' => t('Display this pane in a collapsible fieldset.'),
      'collapsed' => t('Display this pane in a collapsed fieldset.'),
      'none' => t('Do not display this pane in a fieldset.'),
    ),
    '#default_value' => $checkout_pane['fieldset'] ? ($checkout_pane['collapsible'] ? ($checkout_pane['collapsed'] ? 'collapsed' : 'collapsible') : 'default') : 'none',
  );

  // If the checkout pane has a review callback, allow the user to include the
  // pane on the review checkout pane.
  $form['display']['review'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include this pane on the Review checkout pane.'),
    '#default_value' => $checkout_pane['review'],
    '#access' => (boolean) commerce_checkout_pane_callback($checkout_pane, 'review'),
  );

  // If a settings form exists for the specified checkout pane...
  if ($callback = commerce_checkout_pane_callback($checkout_pane, 'settings_form')) {
    // Create a fieldset to hold the checkout pane settings.
    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Checkout pane configuration'),
      '#description' => t('These settings are specific to this checkout pane.'),
    );

    // Add the settings from the callback to the form.
    $form['settings'] += $callback($checkout_pane);
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
    '#submit' => array('commerce_checkout_pane_settings_form_save_submit'),
  );
  $form['reset'] = array(
    '#type' => 'submit',
    '#value' => t('Reset to defaults'),
    '#suffix' => l(t('Cancel'), 'admin/commerce/config/checkout/form'),
    '#submit' => array('commerce_checkout_pane_settings_form_reset_submit'),
  );

  return $form;
}

/**
 * Submit handler for the checkout pane settings form's save button.
 */
function commerce_checkout_pane_settings_form_save_submit($form, &$form_state) {
  // Load and update the pane with values from the form.
  $checkout_pane = commerce_checkout_pane_load($form_state['values']['checkout_pane']['pane_id']);

  // Update the fieldset collapsibility variables.
  switch ($form_state['values']['collapsibility']) {
    case 'collapsible':
      $checkout_pane['fieldset'] = TRUE;
      $checkout_pane['collapsible'] = TRUE;
      $checkout_pane['collapsed'] = FALSE;
      break;
    case 'collapsed':
      $checkout_pane['fieldset'] = TRUE;
      $checkout_pane['collapsible'] = TRUE;
      $checkout_pane['collapsed'] = TRUE;
      break;
    case 'none':
      $checkout_pane['fieldset'] = FALSE;
      $checkout_pane['collapsible'] = FALSE;
      $checkout_pane['collapsed'] = FALSE;
      break;
    case 'default':
    default:
      $checkout_pane['fieldset'] = TRUE;
      $checkout_pane['collapsible'] = FALSE;
      $checkout_pane['collapsed'] = FALSE;
      break;
  }

  // Update the pane's review page visibility.
  $checkout_pane['review'] = $form_state['values']['review'];

  // Save the changes.
  commerce_checkout_pane_save($checkout_pane);

  // Save this checkout pane's settings as if this was a system settings form.
  if (!empty($form['settings'])) {
    foreach (element_children($form['settings']) as $field) {
      if (!empty($form['settings'][$field]['#type'])) {
        // Provide support for containers one level deep.
        if (in_array($form['settings'][$field]['#type'], array('container', 'fieldset'))) {
          foreach (element_children($form['settings'][$field]) as $nested_field) {
            if (isset($form_state['values'][$nested_field])) {
              variable_set($nested_field, $form_state['values'][$nested_field]);
            }
          }
        }
        elseif (isset($form_state['values'][$field])) {
          variable_set($field, $form_state['values'][$field]);
        }
      }
    }
  }

  drupal_set_message(t('Checkout pane saved.'));

  // Redirect to the main checkout form builder page on save.
  $form_state['redirect'] = 'admin/commerce/config/checkout/form';
}

/**
 * Submit handler for the checkout pane settings form's reset button.
 */
function commerce_checkout_pane_settings_form_reset_submit($form, &$form_state) {
  // Reset the display settings for the checkout pane.
  commerce_checkout_pane_reset($form_state['values']['checkout_pane']['pane_id']);

  // Reset this checkout pane's settings as if this was a system settings form.
  if (!empty($form['settings'])) {
    foreach (element_children($form['settings']) as $field) {
      // Provide support for containers one level deep.
      if (in_array($form['settings'][$field]['#type'], array('container', 'fieldset'))) {
        foreach (element_children($form['settings'][$field]) as $nested_field) {
          variable_del($nested_field);
        }
      }
      else {
        variable_del($field);
      }
    }
  }

  drupal_set_message(t('Checkout pane reset.'));
}

/**
 * Builds the checkout completion Rules Overview page.
 */
function commerce_checkout_complete_rules() {
  RulesPluginUI::$basePath = 'admin/commerce/config/checkout/rules';
  $options = array('show plugin' => FALSE);

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled checkout completion rules') . '</h3>';

  $conditions = array('event' => 'commerce_checkout_complete', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['enabled']['rules']['#empty'] = t('There are no active checkout completion rules.');

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled checkout completion rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['disabled']['rules']['#empty'] = t('There are no disabled checkout rules.');

  // Store the function name in the content array to make it easy to alter the
  // contents of this page.
  $content['#page_callback'] = 'commerce_checkout_complete_rules';

  return $content;
}

/**
 * Form callback: confirmation form for manually invoking the checkout
 * completion event for an order.
 *
 * @param $order
 *   The order object to process checkout completion on.
 *
 * @return
 *   The form array to confirm the process.
 *
 * @see confirm_form()
 */
function commerce_checkout_complete_form($form, &$form_state, $order) {
  $form['order_id'] = array(
    '#type' => 'value',
    '#value' => $order->order_id,
  );

  // Build a description of what the user may expect to occur on submission.
  $items = array(
    t('The normal checkout completion process will be invoked on this order.'),
    t('This may involve order status updates and e-mail notifications.'),
  );

  $form = confirm_form($form,
    t('Are you sure you want to simulate checkout completion for order @number?', array('@number' => $order->order_number)),
    'admin/commerce/orders/' . $order->order_id . '/edit',
    '<p>' . theme('item_list', array('items' => $items)) . '</p>',
    t('Simulate checkout completion'),
    t('Cancel')
  );

  return $form;
}

/**
 * Form submit callback for commerce_checkout_complete_form().
 */
function commerce_checkout_complete_form_submit($form, &$form_state) {
  if ($order = commerce_order_load($form_state['values']['order_id'])) {
    commerce_checkout_complete($order);
    drupal_set_message(t('Checkout completion rules have been executed for the order.'));
    $form_state['redirect'] = 'admin/commerce/orders/' . $order->order_id . '/edit';
  }
  else {
    drupal_set_message(t('Order not found.'), 'error');
    $form_state['redirect'] = 'admin/commerce/orders';
  }
}
