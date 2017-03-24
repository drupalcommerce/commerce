<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_plugin_radios' widget.
 *
 * @FieldWidget(
 *   id = "commerce_plugin_radios",
 *   label = @Translation("Plugin radios"),
 *   field_types = {
 *     "commerce_plugin_item"
 *   },
 *  )
 */
class PluginRadiosWidget extends PluginSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['plugin_select']['#plugin_element_type'] = 'radios';
    return $element;
  }

}
