<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Plugin\Field\FieldFormatter\LineItemTable.
 */

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_line_item_table' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_line_item_table",
 *   label = @Translation("Line item table"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class LineItemTable extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $order = $items->getEntity();
    return [
      '#type' => 'view',
      // @todo Allow the view to be configurable.
      '#name' => 'commerce_line_item_table',
      '#arguments' => [$order->id()],
      '#embed' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order' && $field_name == 'line_items';
  }

}
