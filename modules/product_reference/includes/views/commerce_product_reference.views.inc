<?php

/**
 * Provide Product Reference related Views integration.
 */


/**
 * Implements hook_field_views_data().
 */
function commerce_product_reference_field_views_data($field) {
  $data = field_views_field_default_views_data($field);

  // Add a join to commerce_product so that it can be used by the inverse relationship.
  $data[_field_sql_storage_tablename($field)]['table']['join']['commerce_product'] = array(
    'left_field' => 'product_id',
    'field' => $field['field_name'] . '_product_id',
    'extra' => array(
      array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
    ),
  );

  // Build an array of bundles the product reference field appears on.
  $bundles = array();

  foreach ($field['bundles'] as $entity => $entity_bundles) {
    $bundles[] = $entity . ' (' . implode(', ', $entity_bundles) . ')';
  }

  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']['field_name']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['relationship'] = array(
          'title' => t('Referenced product'),
          'help' => t('Appears in: @bundles.', array('@bundles' => implode(', ', $bundles))),
          'base' => 'commerce_product',
          'base field' => 'product_id',
          'handler' => 'views_handler_relationship',
          'label' => t('Product'),
        );
      }
    }
  }

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function commerce_product_reference_views_data_alter(&$data) {
  // Add a node filter that filters by node types with product reference fields.
  $data['node']['is_product_display'] = array(
    'title' => t('Product display'),
    'help' => t('Whether or not the content functions as a product display.'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'commerce_product_reference_handler_filter_node_is_product_display',
      'label' => t('Is a product display'),
      'type' => 'yes-no',
    ),
  );

  // Add the node filter that filters by node types with product reference fields.
  $data['node']['product_display_node_type'] = array(
    'title' => t('Product display content type'),
    'help' => t('Filters by the content type but only shows product display content types as options.'),
    'real field' => 'type',
    'filter' => array(
      'handler' => 'commerce_product_reference_handler_filter_node_type',
    ),
  );

  // Add the line item filter for filtering by line items of a product line item
  // type (based on $line_item_type['product']).
  $data['commerce_line_item']['product_line_item_type'] = array(
    'title' => t('Line item is of a product line item type'),
    'help' => t("Filter line items to those of a product line item type (including but not limited to the default Product line item type)."),
    'filter' => array(
      'handler' => 'commerce_product_reference_handler_filter_product_line_item_type',
    ),
  );

  // Adds inverse relationships between the product and entity types referencing
  // it (for example, nodes).
  foreach (field_info_fields() as $field_name => $field) {
    if ($field['type'] !== 'commerce_product_reference') {
      continue;
    }

    foreach ($field['bundles'] as $entity_type => $bundles) {
      if ($entity_type == 'commerce_line_item') {
        continue;
      }

      $entity_info = entity_get_info($entity_type);
      $data['commerce_product'][$field['field_name']]['relationship'] = array(
        'handler' => 'views_handler_relationship',
        'base' => $entity_info['base table'],
        'base field' => $entity_info['entity keys']['id'],
        'relationship table' => _field_sql_storage_tablename($field),
        'relationship field' => 'entity_id',

        'label' => t('!entity_type referencing products from !field_name', array('!entity_type' => $entity_info['label'], '!field_name' => $field['field_name'])),
        'title' => t('!entity_type referencing products from !field_name', array('!entity_type' => $entity_info['label'],  '!field_name' => $field['field_name'])),
        'help' => t('Relate a product to the !entity_type referencing it.', array('!entity_type' => strtolower($entity_info['label']))),
      );
    }
  }
}
