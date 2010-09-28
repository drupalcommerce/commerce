<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the Payment UI module.
 */


/**
 * Displays the payment transaction View in an order's payment tab.
 */
function commerce_payment_ui_order_tab($order) {
  return commerce_embed_view('commerce_payment_order', 'defaults', array($order->order_id));
}

/**
 * Builds the payment settings page using the Rules UI overview table filtered
 *   to display payment method rules.
 */
function commerce_payment_ui_admin_page() {
  $content['rules']['add'] = array(
    '#theme' => 'menu_local_action',
    '#link' => array(
      'href' => 'admin',
      'title' => t('Link'),
    ),
  );

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled payment rules') . '</h3>';

  $conditions = array('event' => 'commerce_payment_methods', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions);
  $content['enabled']['rules']['#empty'] = t('There are no active payment methods.');

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled payment rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions);
  $content['disabled']['rules']['#empty'] = t('There are no disabled payment methods.');

  return $content;
}
