<?php
// $Id$

function commerce_payment_ui_admin_page() {
  $content['rules']['add'] = array(
    '#theme' => 'menu_local_action',
    '#link' => array(
      'href' => 'admin',
      'title' => t('Link'),
    ),
  );

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled rules') . '</h3>';

  $conditions = array('event' => 'commerce_payment_methods', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions, t('There are no active payment methods.'));

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled rules') . '</h3>';

  $conditions = array('event' => 'commerce_payment_methods', 'plugin' => 'reaction rule', 'active' => FALSE);
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions, t('There are no inactive payment methods.'));

  return $content;
}

