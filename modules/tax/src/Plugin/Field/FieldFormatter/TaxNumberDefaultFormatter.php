<?php

namespace Drupal\commerce_tax\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_tax_number_default' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_tax_number_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "commerce_tax_number"
 *   }
 * )
 */
class TaxNumberDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_verification' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['show_verification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show verification details.'),
      '#default_value' => $this->getSetting('show_verification'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('show_verification')) {
      $summary[] = $this->t('Show verification details.');
    }
    else {
      $summary[] = $this->t('Do not show verification details.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $states = [
      'success' => $this->t('Success'),
      'failure' => $this->t('Failure'),
      'unknown' => $this->t('Unknown'),
    ];

    $elements = [];
    foreach ($items as $delta => $item) {
      $element = [];
      $element['value'] = [
        '#plain_text' => $item->value,
      ];
      if ($this->getSetting('show_verification')) {
        $element['#attached']['library'][] = 'commerce_tax/tax_number';
        if ($item->verification_state && isset($states[$item->verification_state])) {
          $element['verification_state'] = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'title' => $this->t('Verification state: @state', [
                '@state' => $states[$item->verification_state],
              ]),
              'class' => [
                'commerce-tax-number__verification-icon',
                'commerce-tax-number__verification-icon--' . $item->verification_state,
              ],
            ],
          ];
        }
      }

      $elements[$delta] = $element;
    }

    return $elements;
  }

}
