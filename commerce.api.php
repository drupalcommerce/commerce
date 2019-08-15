<?php

/**
 * @file
 * Hooks provided by the Commerce module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations before an inline form is rendered.
 *
 * In addition to hook_commerce_inline_form_alter(), which is called for all
 * inline forms, there is also hook_commerce_inline_form_PLUGIN_ID_alter()
 * which allows targeting an inline form via plugin ID.
 *
 * Generic alter hooks are called before the plugin-specific alter hooks.
 *
 * @param array $inline_form
 *   The inline form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $complete_form
 *   The complete form structure.
 *
 * @see hook_commerce_inline_form_PLUGIN_ID_alter()
 *
 * @ingroup commerce
 */
function hook_commerce_inline_form_alter(array &$inline_form, \Drupal\Core\Form\FormStateInterface $form_state, array &$complete_form) {
  /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormInterface $plugin */
  $plugin = $inline_form['#inline_form'];
  if ($plugin->getPluginId() == 'customer_profile') {
    if ($inline_form['#profile_scope'] == 'billing' && !isset($inline_form['rendered'])) {
      // Modify the billing profile when in "form" mode.
      $inline_form['address']['widget'][0]['#type'] = 'fieldset';
      // Individual address elements (e.g. "address_line1") can only
      // be accessed from an #after_build callback.
      $inline_form['address']['widget'][0]['address']['#after_build'][] = 'your_callback';
    }
  }
}

/**
 * Provide a plugin-specific inline form alteration.
 *
 * Modules can implement hook_commerce_inline_form_PLUGIN_ID_alter()
 * to modify a specific inline form, rather than implementing
 * hook_commerce_inline_form_alter() and checking the plugin ID.
 *
 * Plugin-specific alter hooks are called after the general alter hooks.
 *
 * @param array $inline_form
 *   The inline form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $complete_form
 *   The complete form structure.
 *
 * @see hook_commerce_inline_form_alter()
 *
 * @ingroup commerce
 */
function hook_commerce_inline_form_PLUGIN_ID_alter(array &$inline_form, \Drupal\Core\Form\FormStateInterface $form_state, array &$complete_form) {
  // Modification for the inline form with the given plugin ID goes here.
  // For example, if PLUGIN_ID is "customer_profile" this code would run only
  // for the customer profile form.
  if ($inline_form['#profile_scope'] == 'billing' && !isset($inline_form['rendered'])) {
    // Modify the billing profile when in "form" mode.
    $inline_form['address']['widget'][0]['#type'] = 'fieldset';
    // Individual address elements (e.g. "address_line1") can only
    // be accessed from an #after_build callback.
    $inline_form['address']['widget'][0]['address']['#after_build'][] = 'your_callback';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
