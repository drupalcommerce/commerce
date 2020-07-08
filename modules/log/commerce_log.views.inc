<?php

/**
 * @file
 * Provide views data for the Log module.
 */

/**
 * Implements hook_views_data().
 */
function commerce_log_views_data() {
  $data['views']['commerce_log_admin_comment_form'] = [
    'title' => t('Admin comment form'),
    'help' => t('Displays a form that allows admins with the proper permission to add a log as comment. Requires an entity ID argument.'),
    'area' => [
      'id' => 'commerce_log_admin_comment_form',
    ],
  ];
  return $data;
}
