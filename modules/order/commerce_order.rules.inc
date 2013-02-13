<?php

/**
 * @file
 * Rules integration for orders.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_condition_info().
 */
function commerce_order_rules_condition_info() {
  $conditions = array();

  $conditions['commerce_order_compare_address'] = array(
    'label' => t('Order address component comparison'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order containing the profile reference with the address in question.'),
      ),
      'address_field' => array(
        'type' => 'text',
        'label' => t('Address'),
        'options list' => 'commerce_order_address_field_options_list',
        'description' => t('The address associated with this order whose component you want to compare.'),
        'restriction' => 'input',
      ),
      'address_component' => array(
        'type' => 'text',
        'label' => t('Address component'),
        'options list' => 'commerce_order_address_component_options_list',
        'description' => t('The actual address component you want to compare. Common names of address components are given in parentheses.'),
        'restriction' => 'input',
      ),
      'operator' => array(
        'type' => 'text',
        'label' => t('Operator'),
        'description' => t('The comparison operator.'),
        'optional' => TRUE,
        'default value' => 'equals',
        'options list' => 'commerce_order_address_comparison_operator_options_list',
        'restriction' => 'input',
      ),
      'value' => array(
        'type' => 'text',
        'label' => t('Value'),
        'description' => t('The value to compare against the address component. Bear in mind that addresses using select lists for various components may use a value different from the option you select. For example, countries are selected by name, but the value is the two letter abbreviation.'),
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_compare_address',
    ),
  );

  $conditions['commerce_order_contains_product'] = array(
    'label' => t('Order contains a particular product'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order whose line items should be checked for the specified product. If the specified order does not exist, the comparison will act as if it is against a quantity of 0.'),
      ),
      'product_id' => array(
        'type' => 'text',
        'label' => t('Product SKU'),
        'description' => t('The SKU of the product to look for on the order.'),
      ),
      'operator' => array(
        'type' => 'text',
        'label' => t('Operator'),
        'description' => t('The operator used with the quantity value below to compare the quantity of the specified product on the order.'),
        'default value' => '>=',
        'options list' => 'commerce_numeric_comparison_operator_options_list',
        'restriction' => 'input',
      ),
      'value' => array(
        'type' => 'text',
        'label' => t('Quantity'),
        'default value' => '1',
        'description' => t('The value to compare against the quantity of the specified product on the order.'),
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_contains_product',
    ),
  );

  $conditions['commerce_order_contains_product_type'] = array(
    'label' => t('Order contains products of particular product types'),
    'parameter' => array(
      'commerce_order' => array(
        'label' => t('Order'),
        'type' => 'commerce_order',
        'description' => t('The order whose line items should be checked for the specified product type. If the specified order does not exist, the comparison will act as if it is against a quantity of 0.'),
      ),
      'product_type' => array(
        'label' => t('Product type(s)'),
        'type' => 'list<text>',
        'description' => t('The product type(s) to look for in the order.'),
        'options list' => 'commerce_product_type_options_list',
      ),
      'operator' => array(
        'label' => t('Operator'),
        'type' => 'text',
        'description' => t('The operator used with the quantity value below to compare against the quantity of products matching the specified product type(s) on the order.'),
        'default value' => '>=',
        'options list' => 'commerce_numeric_comparison_operator_options_list',
        'restriction' => 'input',
      ),
      'value' => array(
        'label' => t('Quantity'),
        'type' => 'text',
        'default value' => '1',
        'description' => t('The value to compare against the quantity of products of the specified product type(s) on the order.'),
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_contains_product_type',
    ),
  );

  $conditions['commerce_order_compare_total_product_quantity'] = array(
    'label' => t('Total product quantity comparison'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order whose product line item quantities should be totalled. If the specified order does not exist, the comparison will act as if it is against a quantity of 0.'),
      ),
      'operator' => array(
        'type' => 'text',
        'label' => t('Operator'),
        'description' => t('The comparison operator to use against the total number of products on the order.'),
        'default value' => '>=',
        'options list' => 'commerce_numeric_comparison_operator_options_list',
        'restriction' => 'input',
      ),
      'value' => array(
        'type' => 'text',
        'label' => t('Quantity'),
        'default value' => 1,
        'description' => t('The value to compare against the total quantity of products on the order.'),
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_compare_total_quantity',
    ),
  );

  return $conditions;
}

/**
 * Options list callback: address fields for the address comparison condition.
 */
function commerce_order_address_field_options_list() {
  $options = array();

  // Retrieve a list of all address fields on customer profile bundles.
  $address_fields = commerce_info_fields('addressfield', 'commerce_customer_profile');

  // Loop over every customer profile reference field on orders.
  foreach (commerce_info_fields('commerce_customer_profile_reference', 'commerce_order') as $field_name => $field) {
    // Retrieve the type of customer profile referenced by this field.
    $type = $field['settings']['profile_type'];

    // Loop over every address field looking for any attached to this bundle.
    foreach ($address_fields as $address_field_name => $address_field) {
      if (in_array($type, $address_field['bundles']['commerce_customer_profile'])) {
        // Add it to the options list.
        $instance = field_info_instance('commerce_customer_profile', 'commerce_customer_address', $type);

        $options[commerce_customer_profile_type_get_name($type)][$field_name . '|' . $address_field_name] = check_plain($instance['label']);
      }
    }
  }

  if (empty($options)) {
    drupal_set_message(t('No order addresses could be found for comparison.'), 'error');
  }

  return $options;
}

/**
 * Options list callback: components for the address comparison condition.
 */
function commerce_order_address_component_options_list() {
  return array(
    'country' => t('Country'),
    'name_line' => t('Full name'),
    'first_name' => t('First name'),
    'last_name' => t('Last name'),
    'organisation_name' => t('Company name'),
    'thoroughfare' => t('Thoroughfare (Street address)'),
    'premise' => t('Premise (Building)'),
    'sub_premise' => t('Sub-premise (Suite)'),
    'locality' => t('Locality (City)'),
    'dependent_locality' => t('Dependent locality (Town)'),
    'administrative_area' => t('Administrative area (State / Province)'),
    'sub_administrative_area' => t('Sub-administrative area (District)'),
    'postal_code' => t('Postal code'),
  );
}

/**
 * Options list callback: operators for the address comparison condition.
 */
function commerce_order_address_comparison_operator_options_list() {
  return array(
    'equals' => t('equals'),
    'begins with' => t('begins with'),
    'contains' => t('contains'),
  );
}

/**
 * Condition callback: compares an address component against the given value.
 */
function commerce_order_rules_compare_address($order, $address_field, $component, $operator, $value) {
  list($field_name, $address_field_name) = explode('|', $address_field);

  // If we actually received a valid order...
  if (!empty($order)) {
    $wrapper = entity_metadata_wrapper('commerce_order', $order);

    // And if we can actually find the requested address data...
    if (!empty($wrapper->{$field_name}) && !empty($wrapper->{$field_name}->{$address_field_name})) {
      $address = $wrapper->{$field_name}->{$address_field_name}->value();

      // Make a comparison based on the operator.
      switch ($operator) {
        case 'equals':
          return $address[$component] == $value;
        case 'begins with':
          return strpos($address[$component], $value) === 0;
        case 'contains':
          return strpos($address[$component], $value) !== FALSE;
      }
    }
  }

  return FALSE;
}

/**
 * Condition callback: checks to see if a particular product exists on an order
 * in the specified quantity.
 */
function commerce_order_rules_contains_product($order, $sku, $operator, $value) {
  $products = array($sku => 0);

  // If we actually received a valid order...
  if (!empty($order)) {
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

    // Populate the array of the quantities of the products on the order.
    foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
      if (in_array($line_item_wrapper->type->value(), commerce_product_line_item_types())) {
        // Extract a product ID and quantity from the line item.
        $line_item_sku = $line_item_wrapper->commerce_product->sku->value();
        $quantity = $line_item_wrapper->quantity->value();

        // Update the product's quantity value.
        if (empty($products[$line_item_sku])) {
          $products[$line_item_sku] = $quantity;
        }
        else {
          $products[$line_item_sku] += $quantity;
        }
      }
    }
  }

  // Make a quantity comparison based on the operator.
  switch ($operator) {
    case '<':
      return $products[$sku] < $value;
    case '<=':
      return $products[$sku] <= $value;
    case '=':
      return $products[$sku] == $value;
    case '>=':
      return $products[$sku] >= $value;
    case '>':
      return $products[$sku] > $value;
  }

  return FALSE;
}

/**
 * Condition callback: checks to see if one or more particular product types exist on an order
 * in the specified quantity.
 */
function commerce_order_rules_contains_product_type($order, $product_types, $operator, $value) {
  $quantity = 0;

  // If we actually received a valid order...
  if (!empty($order)) {
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

    // Look for product line items on the order whose products match the
    // specified product types and increment the quantity count accordingly.
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if (in_array($line_item_wrapper->type->value(), commerce_product_line_item_types())) {
        // Extract the product type from the line item.
        $line_item_product_type = $line_item_wrapper->commerce_product->type->value();

        // If the line item product type matches, update the total quantity.
        if (in_array($line_item_product_type, $product_types)) {
          $quantity += $line_item_wrapper->quantity->value();
        }
      }
    }
  }

  // Make a quantity comparison based on the operator.
  switch ($operator) {
    case '<':
      return $quantity < $value;
    case '<=':
      return $quantity <= $value;
    case '=':
      return $quantity == $value;
    case '>=':
      return $quantity >= $value;
    case '>':
      return $quantity > $value;
  }

  return FALSE;
}

/**
 * Condition callback: compares the total quantity of products on an order
 * against the specified quantity.
 */
function commerce_order_rules_compare_total_quantity($order, $operator, $value) {
  $quantity = 0;

  // If we received an order, get the total quantity of products on it.
  if (!empty($order)) {
    $wrapper = entity_metadata_wrapper('commerce_order', $order);

    if (!empty($wrapper->commerce_line_items)) {
      $quantity = commerce_line_items_quantity($wrapper->commerce_line_items, commerce_product_line_item_types());
    }
  }

  // Make a quantity comparison based on the operator.
  switch ($operator) {
    case '<':
      return $quantity < $value;
    case '<=':
      return $quantity <= $value;
    case '=':
      return $quantity == $value;
    case '>=':
      return $quantity >= $value;
    case '>':
      return $quantity > $value;
  }

  return FALSE;
}

/**
 * Implements hook_rules_action_info().
 */
function commerce_order_rules_action_info() {
  $actions = array();

  $actions['commerce_order_update_state'] = array(
    'label' => t('Update the order state'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order to update'),
      ),
      'order_state' => array(
        'type' => 'text',
        'label' => t('Order state'),
        'description' => t('Select the order state whose default status the order will be updated to.'),
        'options list' => 'commerce_order_state_options_list',
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_update_state',
    ),
  );

  $actions['commerce_order_update_status'] = array(
    'label' => t('Update the order status'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order to update'),
      ),
      'order_status' => array(
        'type' => 'text',
        'label' => t('Order status'),
        'options list' => 'commerce_order_status_options_list',
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_update_status',
    ),
  );

  return $actions;
}

/**
 * Rules action: updates an order's status to the default status of the given
 *   order state.
 */
function commerce_order_rules_update_state($order, $name) {
  $order_state = commerce_order_state_load($name);
  commerce_order_status_update($order, $order_state['default_status'], FALSE, NULL, t('Order state updated via Rules.'));
}

/**
 * Rules action: updates an order's status using the Order API.
 */
function commerce_order_rules_update_status($order, $name) {
  commerce_order_status_update($order, $name, FALSE, NULL, t('Order status updated via Rules.'));
}

/**
 * @}
 */
