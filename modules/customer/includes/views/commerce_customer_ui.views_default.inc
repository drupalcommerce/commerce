<?php

/**
 * Views for the default customer UI.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_customer_ui_views_default_views() {
  $views = array();

  // Customer profile admin list at admin/commerce/customer-profiles.
  $view = new view;
  $view->name = 'commerce_customer_profiles';
  $view->description = 'A list of customer profiles of all types.';
  $view->tag = 'commerce';
  $view->base_table = 'commerce_customer_profile';
  $view->human_name = 'Customer profiles';
  $view->core = 0;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Customer profiles';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'administer commerce_customer_profile entities';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '50';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['pager']['options']['id'] = '0';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'profile_id' => 'profile_id',
    'commerce_customer_address' => 'commerce_customer_address',
    'name' => 'name',
    'type' => 'type',
    'status' => 'status',
    'edit_customer_profile' => 'edit_customer_profile',
    'delete_customer_profile' => 'edit_customer_profile',
  );
  $handler->display->display_options['style_options']['default'] = 'profile_id';
  $handler->display->display_options['style_options']['info'] = array(
    'profile_id' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'commerce_customer_address' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'name' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'type' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'status' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'edit_customer_profile' => array(
      'align' => '',
      'separator' => '&nbsp;&nbsp;&nbsp;',
    ),
    'delete_customer_profile' => array(
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['order'] = 'desc';
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['empty'] = FALSE;
  $handler->display->display_options['empty']['area']['content'] = 'No customer profiles have been created yet.';
  $handler->display->display_options['empty']['area']['format'] = 'plain_text';
  /* Relationship: Commerce Customer Profile: Owner */
  $handler->display->display_options['relationships']['uid']['id'] = 'uid';
  $handler->display->display_options['relationships']['uid']['table'] = 'commerce_customer_profile';
  $handler->display->display_options['relationships']['uid']['field'] = 'uid';
  $handler->display->display_options['relationships']['uid']['required'] = 0;
  /* Field: Commerce Customer Profile: Profile ID */
  $handler->display->display_options['fields']['profile_id']['id'] = 'profile_id';
  $handler->display->display_options['fields']['profile_id']['table'] = 'commerce_customer_profile';
  $handler->display->display_options['fields']['profile_id']['field'] = 'profile_id';
  $handler->display->display_options['fields']['profile_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['external'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['profile_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['profile_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['profile_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['profile_id']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['profile_id']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['profile_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['profile_id']['empty_zero'] = 0;
  $handler->display->display_options['fields']['profile_id']['link_to_profile'] = 1;
  /* Field: Fields: Address */
  $handler->display->display_options['fields']['commerce_customer_address']['id'] = 'commerce_customer_address';
  $handler->display->display_options['fields']['commerce_customer_address']['table'] = 'field_data_commerce_customer_address';
  $handler->display->display_options['fields']['commerce_customer_address']['field'] = 'commerce_customer_address';
  $handler->display->display_options['fields']['commerce_customer_address']['label'] = 'Name';
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['external'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['alter']['html'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['commerce_customer_address']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['commerce_customer_address']['hide_empty'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['empty_zero'] = 0;
  $handler->display->display_options['fields']['commerce_customer_address']['click_sort_column'] = 'country';
  $handler->display->display_options['fields']['commerce_customer_address']['type'] = 'addressfield_default';
  $handler->display->display_options['fields']['commerce_customer_address']['settings'] = array(
    'use_widget_handlers' => 0,
    'format_handlers' => array('name-oneline'),
  );
  $handler->display->display_options['fields']['commerce_customer_address']['field_api_classes'] = 0;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid';
  $handler->display->display_options['fields']['name']['label'] = 'User';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  /* Field: Commerce Customer Profile: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'commerce_customer_profile';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['type']['alter']['external'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['type']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['type']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  $handler->display->display_options['fields']['type']['link_to_profile'] = 0;
  /* Field: Commerce Customer Profile: Status */
  $handler->display->display_options['fields']['status']['id'] = 'status';
  $handler->display->display_options['fields']['status']['table'] = 'commerce_customer_profile';
  $handler->display->display_options['fields']['status']['field'] = 'status';
  $handler->display->display_options['fields']['status']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['status']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['status']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['status']['alter']['external'] = 0;
  $handler->display->display_options['fields']['status']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['status']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['status']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['status']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['status']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['status']['alter']['html'] = 0;
  $handler->display->display_options['fields']['status']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['status']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['status']['hide_empty'] = 0;
  $handler->display->display_options['fields']['status']['empty_zero'] = 0;
  $handler->display->display_options['fields']['status']['type'] = 'active-disabled';
  $handler->display->display_options['fields']['status']['not'] = 0;
  /* Field: Commerce Customer Profile: Edit link */
  $handler->display->display_options['fields']['edit_customer_profile']['id'] = 'edit_customer_profile';
  $handler->display->display_options['fields']['edit_customer_profile']['table'] = 'commerce_customer_profile';
  $handler->display->display_options['fields']['edit_customer_profile']['field'] = 'edit_customer_profile';
  $handler->display->display_options['fields']['edit_customer_profile']['label'] = 'Operations';
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['external'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['alter']['html'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['edit_customer_profile']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['edit_customer_profile']['hide_empty'] = 0;
  $handler->display->display_options['fields']['edit_customer_profile']['empty_zero'] = 0;
  /* Field: Commerce Customer Profile: Delete link */
  $handler->display->display_options['fields']['delete_customer_profile']['id'] = 'delete_customer_profile';
  $handler->display->display_options['fields']['delete_customer_profile']['table'] = 'commerce_customer_profile';
  $handler->display->display_options['fields']['delete_customer_profile']['field'] = 'delete_customer_profile';
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['external'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['alter']['html'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['delete_customer_profile']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['delete_customer_profile']['hide_empty'] = 0;
  $handler->display->display_options['fields']['delete_customer_profile']['empty_zero'] = 0;

  /* Display: Admin page */
  $handler = $view->new_display('page', 'Admin page', 'page_1');
  $handler->display->display_options['path'] = 'admin/commerce/customer-profiles/list';
  $handler->display->display_options['menu']['type'] = 'default tab';
  $handler->display->display_options['menu']['title'] = 'List';
  $handler->display->display_options['menu']['weight'] = '-10';
  $handler->display->display_options['tab_options']['type'] = 'normal';
  $handler->display->display_options['tab_options']['title'] = 'Customer profiles';
  $handler->display->display_options['tab_options']['description'] = 'Manage customer profiles and profile types in the store.';
  $handler->display->display_options['tab_options']['weight'] = '0';
  $handler->display->display_options['tab_options']['name'] = 'management';
  $translatables['commerce_customer_profiles'] = array(
    t('Defaults'),
    t('Customer profiles'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('No customer profiles have been created yet.'),
    t('Profile owner'),
    t('Profile ID'),
    t('Name'),
    t('User'),
    t('Type'),
    t('Status'),
    t('Operations'),
    t('Delete link'),
    t('Admin page'),
  );

  $views[$view->name] = $view;

  return $views;
}
