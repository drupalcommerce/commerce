<?php

/**
 * @file
 * Builds placeholder replacement tokens for product-related data.
 */


/**
 * Implements hook_token_info().
 */
function commerce_product_token_info() {
  $type = array(
    'name' => t('Products'),
    'description' => t('Tokens related to individual products.'),
    'needs-data' => 'commerce-product',
  );

  // Tokens for products.
  $product = array();

  $product['product-id'] = array(
    'name' => t('Product ID'),
    'description' => t('The internal numeric ID of the product.'),
  );
  $product['sku'] = array(
    'name' => t('SKU'),
    'description' => t('The human readable product SKU.'),
  );
  $product['type'] = array(
    'name' => t('Type'),
    'description' => t('The machine name of the product type.'),
  );
  $product['type-name'] = array(
    'name' => t('Type name'),
    'description' => t('The human readable name of the product type.'),
  );
  $product['title'] = array(
    'name' => t('Title'),
    'description' => t('The title of the product.'),
  );

  // Chained tokens for products.
  $product['creator'] = array(
    'name' => t('Creator'),
    'description' => t('The creator of the product.'),
    'type' => 'user',
  );
  $product['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the product was created.'),
    'type' => 'date',
  );
  $product['changed'] = array(
    'name' => t('Date updated'),
    'description' => t('The date the product was last updated.'),
    'type' => 'date',
  );

  return array(
    'types' => array('commerce-product' => $type),
    'tokens' => array('commerce-product' => $product),
  );
}

/**
 * Implements hook_tokens().
 */
function commerce_product_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }

  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'commerce-product' && !empty($data['commerce-product'])) {
    $product = $data['commerce-product'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the product.
        case 'product-id':
          $replacements[$original] = $product->product_id;
          break;

        case 'sku':
          $replacements[$original] = $sanitize ? check_plain($product->sku) : $product->sku;
          break;

        case 'type':
          $replacements[$original] = $sanitize ? check_plain($product->type) : $product->type;
          break;

        case 'type-name':
          $replacements[$original] = $sanitize ? check_plain(commerce_product_type_get_name($product->type)) : commerce_product_type_get_name($product->type);
          break;

        case 'title':
          $replacements[$original] = $sanitize ? check_plain($product->title) : $product->title;
          break;

        // Default values for the chained tokens handled below.
        case 'creator':
          if (!$product->uid) {
            $name = variable_get('anonymous', t('Anonymous'));
          }
          else {
            $creator = user_load($product->uid);
            $name = $creator->name;
          }
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;
        case 'created':
          $replacements[$original] = format_date($product->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($product->changed, 'medium', '', NULL, $language_code);
          break;
      }
    }

    if ($creator_tokens = token_find_with_prefix($tokens, 'creator')) {
      $creator = user_load($product->uid);
      $replacements += token_generate('user', $creator_tokens, array('user' => $creator), $options);
    }

    foreach (array('created', 'changed') as $date) {
      if ($created_tokens = token_find_with_prefix($tokens, $date)) {
        $replacements += token_generate('date', $created_tokens, array('date' => $product->{$date}), $options);
      }
    }
  }

  return $replacements;
}
