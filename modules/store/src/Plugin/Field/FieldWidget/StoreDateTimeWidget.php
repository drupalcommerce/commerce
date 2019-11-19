<?php

namespace Drupal\commerce_store\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Plugin implementation of the 'commerce_store_datetime' widget.
 *
 * Used for entering date/time values that are going to be used in
 * the store timezone, as opposed to the user's timezone.
 *
 * The store timezone is not known at entry time, since the parent entity
 * might belong to multiple stores. Instead, the store timezone is assigned
 * in the date/time getter, before the value is used.
 *
 * The "datetime_default" widget performs timezone conversion, assuming
 * that the entered value is in the user's timezone, and converting it to
 * UTC on storage. This widget ensures there is no conversion.
 * If the user enters "2019-10-31 23:59:00", the value is stored and loaded
 * as-is. Once the timezone is assigned, it becomes "2019-10-31 23:59:00 EST",
 * assuming that the current store's timezone is EST.
 *
 * @FieldWidget(
 *   id = "commerce_store_datetime",
 *   label = @Translation("Date and time (Store timezone)"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class StoreDateTimeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = [
      '#type' => 'datetime',
      '#title' => $this->fieldDefinition->getLabel(),
      '#description' => $this->fieldDefinition->getDescription(),
      '#required' => $element['#required'],
      '#date_increment' => 1,
      '#date_timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
      '#default_value' => NULL,
    ];
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $element['value']['#date_time_element'] = 'none';
      $element['value']['#date_time_format'] = '';
    }
    if ($items[$delta]->date) {
      $date = new DrupalDateTime($items[$delta]->value, DateTimeItemInterface::STORAGE_TIMEZONE);
      if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
        $date->setDefaultDateTime();
      }
      $element['value']['#default_value'] = $date;
    }

    // When the field is optional, it is considered more user friendly
    // to hide the date/time widget behind a checkbox.
    $field = $this->fieldDefinition;
    if (!$field->isRequired() && $field->getSetting('datetime_optional_label')) {
      $checkbox_parents = array_merge($form['#parents'], [$field->getName(), $delta, 'has_value']);
      $checkbox_path = array_shift($checkbox_parents);
      $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';

      $element['has_value'] = [
        '#type' => 'checkbox',
        '#title' => $field->getSetting('datetime_optional_label'),
        '#default_value' => !empty($element['value']['#default_value']),
        '#access' => empty($element['value']['#default_value']),
      ];
      $element['value']['#weight'] = 10;
      $element['value']['#description'] = '';
      // Workaround for #2419131.
      $element['container']['#type'] = 'container';
      $element['container'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $element['container']['value'] = $element['value'];
      unset($element['value']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Convert the value back from DrupalDateTime to the storage format.
    $datetime_type = $this->getFieldSetting('datetime_type');
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    if ($datetime_type === DateTimeItem::DATETIME_TYPE_DATE) {
      $storage_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    }

    foreach ($values as &$item) {
      if (!empty($item['container']['value'])) {
        $item['value'] = $item['container']['value'];
        unset($item['container']);
      }
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item['value'];
        $item['value'] = $date->format($storage_format);
      }
    }

    return $values;
  }

}
