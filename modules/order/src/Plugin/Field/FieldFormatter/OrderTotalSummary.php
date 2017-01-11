<?php

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_order_total_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_order_total_summary",
 *   label = @Translation("Order total summary"),
 *   field_types = {
 *     "commerce_price",
 *   },
 * )
 */
class OrderTotalSummary extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $order = $items->getEntity();
    return [
      '#theme' => 'commerce_order_total_summary',
      '#totals' => \Drupal::service('commerce_order.order_total_summary')->buildTotals($order),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order' && $field_name == 'total_price';
  }

}
