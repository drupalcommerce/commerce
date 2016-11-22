<?php

namespace Drupal\commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;

/**
 * Plugin implementation of the 'commerce_auto_sku' widget.
 *
 * @FieldWidget(
 *   id = "commerce_auto_sku",
 *   label = @Translation("Commerce auto SKU"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ProductVariationSkuWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'uniqid_enabled' => TRUE,
      'more_entropy' => FALSE,
      'prefix' => 'sku-',
      'suffix' => '',
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $none = $this->t('None');
    $settings = $this->getSettings();
    $element['uniqid_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable unique auto SKU values generation'),
      '#default_value' => $settings['uniqid_enabled'],
    ];
    $element['more_entropy'] = [
      '#type' => 'checkbox',
      '#title_display' => 'before',
      '#title' => $this->t('More unique'),
      '#description' => $this->t('If unchecked the SKU (without prefix and suffix) will look like this: <strong>@short</strong>. If checked, like this: <strong>@long</strong>. <a href=":uniqid_href" target="_blank">Read more</a>', [':uniqid_href' => 'http://php.net/manual/en/function.uniqid.php', '@short' => uniqid(), '@long' => uniqid('', TRUE)]),
      '#default_value' => $settings['more_entropy'],
      '#states' => [
        'visible' => [':input[name*="uniqid_enabled"]' => ['checked' => TRUE]],
      ],
    ];
    $element['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SKU prefix'),
      '#default_value' => $settings['prefix'],
      '#placeholder' => $none,
    ];
    $element['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SKU suffix'),
      '#default_value' => $settings['suffix'],
      '#placeholder' => $none,
    ];
    $element['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of SKU field'),
      '#default_value' => $settings['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#placeholder' => $none,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $none = $this->t('None');
    $settings = $this->getSettings();
    $sku = uniqid($settings['prefix'], $settings['more_entropy']) . $settings['suffix'];
    $settings['auto SKU sample'] = $settings['uniqid_enabled'] ? $sku : $none;
    unset($settings['uniqid_enabled'], $settings['more_entropy']);
    foreach ($settings as $name => $value) {
      $value = empty($settings[$name]) ? $none : $value;
      $summary[] = "{$name}: {$value}";
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
    ];

    return $element;
  }

}
