<?php

namespace Drupal\commerce_order\Plugin\Field\FieldType;

use Drupal\commerce_order\Adjustment;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_adjustment' field type.
 *
 * @FieldType(
 *   id = "commerce_adjustment",
 *   label = @Translation("Adjustment"),
 *   description = @Translation("Stores adjustments to the parent entity's price."),
 *   category = @Translation("Commerce"),
 *   list_class = "\Drupal\commerce_order\Plugin\Field\FieldType\AdjustmentItemList",
 *   no_ui = TRUE,
 *   default_widget = "commerce_adjustment_default",
 * )
 */
class AdjustmentItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || !$this->value instanceof Adjustment;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (is_array($values)) {
      // The property definition causes the adjustment to be in 'value' key.
      $values = reset($values);
    }
    if (!$values instanceof Adjustment) {
      $values = NULL;
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The adjustment value.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

}
