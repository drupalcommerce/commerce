<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
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
    $element['target_plugin_id']['#type'] = 'radios';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxRefresh(&$form, FormStateInterface $form_state) {
    $element_parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    return NestedArray::getValue($form, $element_parents);
  }

}
