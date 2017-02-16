<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'commerce_optional_date' widget.
 *
 * @FieldWidget(
 *   id = "commerce_optional_date",
 *   label = @Translation("Optional date"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class OptionalDateWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      $title = $this->t('Provide a date');
    }
    else {
      $title = $this->t('Provide a date and time');
    }

    $element['has_value'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#default_value' => !empty($element['value']['#default_value']),
      '#access' => empty($element['value']['#default_value']),
    ];
    $element['value']['#weight'] = 10;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);

    $field_name = $element['#field_name'];
    foreach (Element::children($element) as $key) {
      $element[$key]['value']['date']['#states'] = [
        'visible' => [
          ':input[name="' . $field_name . '[' . $key . '][has_value]"]' => ['checked' => TRUE],
        ],
      ];
    }

    return $element;
  }

}
