<?php

namespace Drupal\commerce_price\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_list_price' widget.
 *
 * @FieldWidget(
 *   id = "commerce_list_price",
 *   label = @Translation("List price"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class ListPriceWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $checkbox_parents = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 0, 'has_value']);
    $checkbox_path = array_shift($checkbox_parents);
    $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';

    $element['has_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide a list price'),
    ];
    $element['value'] = [
      '#type' => 'commerce_price',
      '#title' => $element['#title'],
      '#available_currencies' => array_filter($this->getFieldSetting('available_currencies')),
      '#states' => [
        'visible' => [
          ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!$items[$delta]->isEmpty()) {
      $element['has_value']['#default_value'] = TRUE;
      $element['value']['#default_value'] = $items[$delta]->toPrice()->toArray();
    }
    // Remove the checkbox if the list_price field is required.
    if ($element['#required']) {
      $element['has_value']['#access'] = FALSE;
      $element['has_value']['#default_value'] = TRUE;
      $element['value']['#required'] = TRUE;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item = !empty($item['has_value']) ? $item['value'] : NULL;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() == 'list_price';
  }

}
