<?php

namespace Drupal\commerce_promotion\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'commerce_end_date' widget.
 *
 * @FieldWidget(
 *   id = "commerce_end_date",
 *   label = @Translation("End date"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class EndDateWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['has_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide an end date'),
      '#default_value' => !empty($element['value']['#default_value']),
      '#access' => empty($element['value']['#default_value']),
    ];
    $element['value']['#weight'] = 10;
    $element['value']['#description'] = '';
    // Workaround for #2419131.
    $field_name = $this->fieldDefinition->getName();
    $element['container']['#type'] = 'container';
    $element['container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . '[' . $delta . '][has_value]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['container']['value'] = $element['value'];
    unset($element['value']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['container']['value']) && $item['container']['value'] instanceof DrupalDateTime) {
        $date = $item['container']['value'];
        // Adjust the date for storage.
        datetime_date_default_time($date);
        $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['value'] = $date->format(DATETIME_DATE_STORAGE_FORMAT);
        unset($item['container']);
      }
    }
    return $values;
  }

}
