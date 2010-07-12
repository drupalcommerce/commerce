<?php
// $Id$

/**
 * @file
 * Administrative callbacks for the Checkout module.
 */

/**
 * Builds the admin settings form.
 */
function commerce_checkout_settings_form() {
  $form = array();

  $form['anonymous'] = array(
    '#type' => 'fieldset',
    '#title' => t('Anonymous checkout settings'),
    '#description' => t('These settings alter the behavior of checkout when an anonymous user completes checkout.'),
  );
  $form['anonymous']['commerce_checkout_anonymous_account'] = array(
    '#type' => 'radios',
    '#title' => t('Account creation behavior'),
    '#description' => t('This setting affects account creation for anonymous users who do not have pre-existing user accounts.'),
    '#options' => array(
      0 => t('Do not create a user account for the order.'),
      1 => t('Create a blocked user account for the order.'),
      2 => t('Create an active user account for the order.'),
    ),
    '#default_value' => variable_get('commerce_checkout_anonymous_account', 2),
  );
  $form['anonymous']['commerce_checkout_anonymous_notify'] = array(
    '#type' => 'radios',
    '#title' => t('Account creation notification'),
    '#description' => t('The notification settings only apply to active user accounts created upon checkout completion.'),
    '#options' => array(
      0 => t('Do not notify the user of the new account.'),
      1 => t('Notify the user on the checkout completion page.'),
      2 => t('Notify the user through a new account e-mail based on your user settings.'),
      3 => t('Notify the user on the checkout completion page and through the new account e-mail.'),
    ),
    '#default_value' => variable_get('commerce_checkout_anonymous_notify', 3),
  );
  $form['anonymous']['commerce_checkout_anonymous_login'] = array(
    '#type' => 'checkbox',
    '#title' => t('Login customers who have user accounts created for them upon checkout completion.'),
    '#default_value' => variable_get('commerce_checkout_anonymous_login', FALSE),
  );

  global $base_url;
  $form['redirection'] = array(
      '#type'  => 'fieldset',
      '#title' => t('Redirection Settings'),
  );
  $form['redirection']['commerce_checkout_empty_cart_redirect'] = array(
      '#type'  => 'textfield',
      '#title' => t('Redirect destination from checkout, when cart is empty.'),
      '#default_value' => variable_get('commerce_checkout_empty_cart_redirect', NULL),
      '#description' => t("This will be relative to your site location <em>@url</em> <br />Valid values are alias urls or actual drupal urls, examples: <em>product/purple-sweater</em> or <em>node/12</em><br />Leave blank or set to <em>&lt;front&gt;</em> to redirect to site home page.", array("@url" => $base_url ."/")),
  );
  return system_settings_form($form);
}

/**
 * Build the checkout form builder, adding in data for the checkout pages and
 *   the appropriate fields to enable tabledrag on the checkout panes.
 */
function commerce_checkout_builder_form() {
  $form = array();

  // Load an array of all available checkout pages.
  $checkout_pages = commerce_checkout_pages();

  // Add a "disabled" pseudo-page.
  $checkout_pages['disabled'] = (object) array('page_id' => 'disabled', 'title' => t('Disabled'));

  $form['checkout_pages'] = array(
    '#type' => 'value',
    '#value' => $checkout_pages,
  );

  // Create arrays for checkout panes in each of the pages.
  foreach (array_keys($checkout_pages) as $page_id) {
    $form['page'][$page_id]['panes'] = array('#tree' => TRUE);
  }

  // Loop through all the checkout panes on the site.
  $panes = commerce_checkout_panes();

  foreach ($panes as $pane_id => $checkout_pane) {
    // Determine a checkout pane's current checkout page.
    $page_id = $checkout_pane->enabled ? $checkout_pane->page : 'disabled';

    // If the page no longer exists, place the pane on the first page.
    if (empty($checkout_pages[$page_id])) {
      reset($checkout_pages);
      $page_id = key($checkout_pages);
    }

    // Add the pane's name to the form array.
    $form['page'][$page_id]['panes'][$pane_id]['name'] = array(
      '#markup' => check_plain($checkout_pane->name),
    );

    // Add the select field for the pane's checkout page.
    $form['page'][$page_id]['panes'][$pane_id]['page'] = array(
      '#type' => 'select',
      '#options' => drupal_map_assoc(array_keys($checkout_pages)),
      '#default_value' => $checkout_pane->page,
      '#attributes' => array('class' => array('checkout-pane-page checkout-pane-page-'. $checkout_pane->page)),
    );

    // Add the select field for the pane's weight.
    $form['page'][$page_id]['panes'][$pane_id]['weight'] = array(
      '#type' => 'weight',
      '#delta' => 20,
      '#default_value' => $checkout_pane->weight,
      '#attributes' => array('class' => array('checkout-pane-weight checkout-pane-weight-'. $checkout_pane->page)),
    );

    // Add a configuration link for the pane.
    $form['page'][$page_id]['panes'][$pane_id]['ops'] = array(
      '#markup' => l(t('configure'), 'admin/commerce/config/checkout/form/pane/'. $pane_id),
    );
  }

  $form['actions'] = array(
    '#type' => 'container',
    '#tree' => FALSE,
    '#attributes' => array('class' => array('form-actions')),
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
 * Submit handler for the checkout builder form's save button.
 */
function commerce_checkout_builder_form_save_submit($form, &$form_state) {
  // Loop through each of the checkout panes.
  if (!empty($form_state['values']['panes'])) {
    foreach ($form_state['values']['panes'] as $pane_id => $data) {
      // Load and update the checkout pane array.
      $checkout_pane = commerce_checkout_pane_load($pane_id);
      $checkout_pane->weight = $data['weight'];

      // Accommodate the "Disabled" pseudo-page in the form.
      if ($data['page'] == 'disabled') {
        $checkout_pane->enabled = FALSE;
        unset($checkout_pane->page);
      }
      else {
        $checkout_pane->enabled = TRUE;
        $checkout_pane->page = $data['page'];
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
  db_query("TRUNCATE TABLE {commerce_checkout_pane}");

  drupal_set_message(t('Checkout pane positions reset.'));
}

/**
 * Theme the checkout pages form to enable tabledrag.
 */
function theme_commerce_checkout_builder_form($variables) {
  $form = $variables['form'];

  drupal_add_css(drupal_get_path('module', 'commerce_checkout') .'/theme/commerce_checkout_admin.css');
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
      array('data' => $checkout_page->title, 'colspan' => 4),
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
  $form = array();

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
    ),
    '#default_value' => $checkout_pane->collapsible ? ($checkout_pane->collapsed ? 'collapsed' : 'collapsible') : 'default',
  );
  $form['display']['review'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include this pane on the optional checkout review page.'),
    '#description' => t('The review page can be toggled on the !link form.', array('!link' => l(t('checkout settings'), 'admin/commerce/config/checkout'))),
    '#default_value' => $checkout_pane->review,
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
  $checkout_pane = commerce_checkout_pane_load($form_state['values']['checkout_pane']->pane_id);

  // Update the fieldset collapsibility variables.
  switch ($form_state['values']['collapsibility']) {
    case 'collapsible':
      $checkout_pane->collapsible = TRUE;
      $checkout_pane->collapsed = FALSE;
      break;
    case 'collapsed':
      $checkout_pane->collapsible = TRUE;
      $checkout_pane->collapsed = TRUE;
      break;
    case 'default':
    default:
      $checkout_pane->collapsible = FALSE;
      $checkout_pane->collapsed = FALSE;
      break;
  }

  // Update the pane's review page visibility.
  $checkout_pane->review = $form_state['values']['review'];

  // Save the changes.
  commerce_checkout_pane_save($checkout_pane);

  // Save this checkout pane's settings as if this was a system settings form.
  if (!empty($form['settings'])) {
    foreach (element_children($form['settings']) as $field) {
      variable_set($field, $form_state['values'][$field]);
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
  commerce_checkout_pane_reset($form_state['values']['checkout_pane']->pane_id);

  // Reset this checkout pane's settings as if this was a system settings form.
  if (!empty($form['settings'])) {
    foreach (element_children($form['settings']) as $field) {
      variable_del($field);
    }
  }

  drupal_set_message(t('Checkout pane reset.'));
}
