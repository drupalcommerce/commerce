<?php

namespace Drupal\commerce\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_plugin_item_default' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_plugin_item_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "commerce_plugin_item"
 *   }
 * )
 */
class PluginItemDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $target_definition = $item->getTargetDefinition();
      if (!empty($target_definition['label'])) {
        $elements[$delta] = [
          '#markup' => $target_definition['label'],
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $target_definition['id'],
        ];
      }
    }

    return $elements;
  }

}
