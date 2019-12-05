<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\CommerceEntityViewsData;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides views data for promotions.
 */
class PromotionViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  protected function processViewsDataForDatetime($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    parent::processViewsDataForDatetime($table, $field_definition, $views_field, $field_column_name);

    // Promotion date/time fields are always used in the store timezone.
    if ($field_column_name == 'value') {
      $views_field['field']['default_formatter'] = 'commerce_store_datetime';
      $views_field['filter']['id'] = 'commerce_store_datetime';
    }
  }

}
