<?php

namespace Drupal\commerce_tax\Plugin\Field\FieldFormatter;

use Drupal\commerce\UrlData;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
    $entity = $items->getEntity();

    $elements = [];
    foreach ($items as $delta => $item) {
      $element = [];
      $element['value'] = [
        '#plain_text' => $item->value,
      ];
      if ($this->getSetting('show_verification')) {
        $element['#attached']['library'][] = 'commerce_tax/tax_number';
        $context = UrlData::encode([
          $entity->getEntityTypeId(),
          $entity->id(),
          $this->fieldDefinition->getName(),
          $this->viewMode,
        ]);

        if ($item->verification_result) {
          $element['value'] = [
            '#type' => 'link',
            '#title' => $item->value,
            '#url' => Url::fromRoute('commerce_tax.verification_result', [
              'tax_number' => $item->value,
              'context' => $context,
            ]),
            '#attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 500,
                'title' => $item->value,
              ]),
            ],
          ];
        }
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

          $options = [
            'query' => [
              'destination' => Url::fromRoute('<current>')->toString(),
            ],
          ];
          $element['reverify'] = [
            '#type' => 'container',
            '#access' => $item->verification_state == VerificationResult::STATE_UNKNOWN,
          ];
          $element['reverify']['link'] = [
            '#type' => 'link',
            '#title' => $this->t('Reverify'),
            '#url' => Url::fromRoute('commerce_tax.verify', [
              'tax_number' => $item->value,
              'context' => $context,
            ], $options),
          ];
        }
      }

      $elements[$delta] = $element;
    }

    return $elements;
  }

}
