<?php

/**
 * @file
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * Defines currencies available to the Commerce currency formatting and price APIs.
 *
 * By default Drupal Commerce defines all actively traded currencies according
 * to ISO 4217. Additional currencies may be added by modules that depend on
 * alternate or pseudo-currency definitions.
 *
 * @return
 *   An array of currency data arrays keyed by three character currency codes.
 *   Currency data arrays should include:
 *   - code: The uppercase alphabetic currency code. For example USD.
 *   - numeric_code: The numeric currency code. According to ISO4217 this code
 *     consists of three digits and first digit can be a zero.
 *   - symbol: The currency symbol. For example $.
 *   - name: The name of the currency. Translatable.
 *   - symbol_placement: Defines where the currency symbol has to be placed for
 *      display. Allowed values: before, after, hidden.
 *   - symbol_spacer: The spacer to put between the price amount and a currency
 *     symbol that appears after the amount; defaults to ' '.
 *   - code_placement: Defines where the currency code has to be placed for
 *      display. Allowed values: before, after, hidden.
 *   - code_spacer: The spacer to put between the price amount and currency code
 *     whether the code is displayed before or after the amount; defaults to ' '.
 *   - minor_unit: Name of the minor unit of the currency. For example Cent.
 *     Translatable
 *   - major_unit: Name of the major unit of the currency. For example Dollar.
 *     Translatable
 *   - rounding_step: Defines which stepping has to is used for price rounding.
 *     For example Swiss Francs use a rounding_step of 0.05. This means a
 *     price like 10.93 is converted to 10.95. Currently only the steps
 *     0.5,0.05... and 0.2, 0.02 ... are supported. This value has to be
 *     defined as string, otherwise the rounding results can be unpredictable.
 *     Default: 0 (no special rounding)
 *   - decimals: The number of decimals to display.
 *     Default: 2
 *   - thousands_separator: The char to split the value in groups of thousand.
 *     Default: ,
 *   - decimal_separator: The char to split the integer from the decimal part.
 *     Default: .
 *   - format_callback: Custom callback function to format a price value.
 *   - conversion_callback: Custom callback function to convert a price amount
 *     from one currency into another.
 *   - conversion_rate: The conversion rate of this currency calculated against
 *     the base currency, expressed as a decimal value denoting the value of
 *     one major unit of this currency when converted to the base currency.
 *     Default: 1
 *
 * @see hook_commerce_currency_info_alter()
 */
function hook_commerce_currency_info() {
  return array (
    'CHF' => array(
      'code' => 'CHF',
      'numeric_code' => '756',
      'symbol' => 'Fr.',
      'name' => t('Swiss Franc'),
      'symbol_placement' => 'before',
      'code_placement' => 'before',
      'minor_unit' => t('Rappen'),
      'major_unit' => t('Franc'),
      'rounding_step' => '0.05',
    ),
  );
}

/**
 * Allows modules to alter Commerce currency definitions.
 *
 * By default Commerce provides all active currencies according to ISO 4217.
 * This hook allows you to change the formatting properties of existing
 * definitions.
 *
 * Additionally, because every currency's default conversion rate is 1, this
 * hook can be used to populate currency conversion rates with meaningful
 * values. Conversion rates can be calculated using any currency as the base
 * currency as long as the same base currency is used for every rate.
 *
 * @see hook_commerce_currency_info()
 */
function hook_commerce_currency_info_alter(&$currencies, $langcode) {
  $currencies['CHF']['code_placement'] = 'after';
}

/**
 * Allows modules to deny or provide access for a user to perform a non-view
 * operation on an entity before any other access check occurs.
 *
 * Modules implementing this hook can return FALSE to provide a blanket
 * prevention for the user to perform the requested operation on the specified
 * entity. If no modules implementing this hook return FALSE but at least one
 * returns TRUE, then the operation will be allowed, even for a user without
 * role based permission to perform the operation.
 *
 * If no modules return FALSE but none return TRUE either, normal permission
 * based checking will apply.
 *
 * @param $op
 *   The request operation: update, create, or delete.
 * @param $entity
 *   The entity to perform the operation on.
 * @param $account
 *   The user account whose access should be determined.
 * @param $entity_type
 *   The machine-name of the entity type of the given $entity.
 *
 * @return
 *   TRUE or FALSE indicating an explicit denial of permission or a grant in the
 *   presence of no other denials; NULL to not affect the access check at all.
 */
function hook_commerce_entity_access($op, $entity, $account, $entity_type) {
  // No example.
}

/**
 * Allows modules to alter the conditions used on the query to grant view access
 * to a Commerce entity of the specified ENTITY TYPE.
 *
 * The Commerce module defines a generic implementation of hook_query_alter() to
 * determine view access for its entities, commerce_entity_access_query_alter().
 * This function is called by modules defining Commerce entities from their
 * view access altering functions to apply a standard set of permission based
 * conditions for determining a user's access to view the given entity.
 *
 * @param $conditions
 *   The OR conditions group used for the view access query.
 * @param $context
 *   An array of contextual information including:
 *   - account: the account whose access to view the entity is being checked
 *   - entity_type: the type of entity in the query
 *   - base_table: the name of the table for the entity type
 *
 * @see commerce_entity_access_query_alter()
 * @see commerce_cart_commerce_entity_access_condition_commerce_order_alter()
 */
function hook_commerce_entity_access_condition_ENTITY_TYPE_alter() {
  // See the Cart implementation of the hook for an example, as the Cart module
  // alters the view query to grant view access of orders to anonymous users who
  // own them based on the order IDs stored in the anonymous session.
}

/**
 * Allows modules to alter the conditions used on the query to grant view access
 * to a Commerce entity.
 *
 * This hook uses the same parameters as the entity type specific hook but is
 * invoked after it.
 *
 * @see hook_commerce_entity_access_condition_ENTITY_TYPE_alter()
 */
function hook_commerce_entity_access_condition_alter() {
  // No example.
}

/**
 * Allows modules to alter newly created Commerce entities.
 *
 * Commerce's default entity controller, DrupalCommerceEntityController, invokes
 * this hook after creating a new entity object using either a class specified
 * by the entity type info or a stdClass. Using this hook, you can alter the
 * entity before it is returned to any of our entity "new" API functions such
 * as commerce_product_new().
 *
 * @param $entity_type
 *   The machine-name type of the entity.
 * @param $entity
 *   The entity object that was just created.
 */
function hook_commerce_entity_create_alter($entity_type, $entity) {
  // No example.
}
