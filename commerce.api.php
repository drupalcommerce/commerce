<?php

/**
 * @file
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * Defines currencies available to commerce.
 *
 * By default commerce provides all active currencies according to ISO 4217.
 * Make sure to use the translate function t() for translatable properties.
 *
 * @return
 *   An array of information about the currencies commerce should provide.
 *   The array contains a sub-array for each currency, with the currency name
 *   as the key.
 *   Possible attributes for each sub-array are:
 *   - code: The uppercase alphabetic currency code.
 *      For example USD.
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
