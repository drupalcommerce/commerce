<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the Product Reference UI module.
 */


/**
 * Builds the product sell price calculation Rules Overview page.
 */
function commerce_product_reference_ui_sell_price_rules() {
  RulesPluginUI::$basePath = 'admin/commerce/products/pricing-rules';

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled sell price calculation rules') . '</h3>';

  $conditions = array('event' => 'commerce_product_calculate_sell_price', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions);
  $content['enabled']['rules']['#empty'] = t('There are no active sell price calculation rules.');

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled sell price calculation rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions);
  $content['disabled']['rules']['#empty'] = t('There are no disabled sell price calculation rules.');

  return $content;
}
