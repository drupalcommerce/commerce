<?php

/**
 * An extension of the default Views field handler that supports aggregating
 * price fields.
 */
class commerce_price_handler_field_commerce_price extends views_handler_field_field {
  function get_value($values, $field = NULL) {
    // If this field has aggregation enabled...
    if (!empty($this->group_fields)) {
      // Loop over the aggregated database fields looking for aggregation on the
      // price field's amount column.
      foreach ($this->group_fields as $field_name => $column) {
        // If we find it, whether currency_code aggregation is enabled or not,
        // we simulate it / override it and provide a default value in the
        // $values array that uses the currency code of the representative
        // entity used in parent::get_value() or the site's default currency.
        if ($field_name == 'amount') {
          // Generate a pseudo column name that will not have a collision,
          // because it's based on extending a column name that will never
          // otherwise have _currency_code appended to it. Note that this means
          // you cannot use aggregation on multi-currency price fields or
          // outside of an entity context where field values may use a currency
          // other than the site's default currency.
          $pseudo_column = $column . '_currency_code';
          $this->group_fields['currency_code'] = $pseudo_column;
          $this->aliases[$pseudo_column] = $pseudo_column;

          // Extract the entity from the values array.
          $entity = $values->_field_data[$this->field_alias]['entity'];
          $entity_type = $values->_field_data[$this->field_alias]['entity_type'];
          $langcode = $this->field_language($entity_type, $entity);

          // And finally put a valid currency code in the pseudo column value.
          $currency_code = NULL;

          if (!empty($entity->{$this->definition['field_name']}[$langcode])) {
            $items = $entity->{$this->definition['field_name']}[$langcode];
            $delta = key($items);

            if (!empty($items[$delta]['currency_code'])) {
              $currency_code = $items[$delta]['currency_code'];
            }
          }

          if (empty($currency_code)) {
            $values->$pseudo_column = commerce_default_currency();
          }
          else {
            $values->$pseudo_column = $currency_code;
          }
        }
      }
    }

    return parent::get_value($values, $field);
  }
}
