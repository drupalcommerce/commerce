<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\TaxZone;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Norwegian VAT tax type.
 *
 * @CommerceTaxType(
 *   id = "norwegian_vat",
 *   label = "Norwegian VAT",
 * )
 */
class NorwegianVat extends LocalTaxTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['rates'] = $this->buildRateSummary();
    // Replace the phrase "tax rates" with "VAT rates" to be more precise.
    $form['rates']['#markup'] = $this->t('The following VAT rates are provided:');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildZones() {
    $zones = [];
    $zones['no'] = new TaxZone([
      'id' => 'no',
      'label' => $this->t('Norway'),
      'display_label' => $this->t('VAT'),
      'territories' => [
        ['country_code' => 'NO'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'percentages' => [
            ['number' => '0.25', 'start_date' => '2012-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'percentages' => [
            ['number' => '0.15', 'start_date' => '2012-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'percentages' => [
            ['number' => '0.08', 'start_date' => '2012-01-01'],
          ],
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'percentages' => [
            ['number' => '0', 'start_date' => '2012-01-01'],
          ],
        ],
      ],
    ]);

    return $zones;
  }

}
