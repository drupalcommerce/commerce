<?php

namespace Drupal\commerce;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\views\EntityViewsData;

/**
 * Provides improvements to core's generic views integration for entities.
 */
class CommerceEntityViewsData extends EntityViewsData {

  /**
   * Corrects the views data for commerce_price base fields.
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForCommercePrice($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'number') {
      $views_field['filter']['id'] = 'numeric';
    }
  }

}
