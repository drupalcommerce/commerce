<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides the Canadian sales tax type.
 *
 * @CommerceTaxType(
 *   id = "canadian_sales_tax",
 *   label = "Canadian sales tax",
 * )
 */
class CanadianSalesTax extends LocalTaxTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['rates'] = $this->buildRateSummary();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function matchesAddress(StoreInterface $store) {
    return $store->getAddress()->getCountryCode() == 'CA';
  }

  /**
   * {@inheritdoc}
   */
  protected function matchesRegistrations(StoreInterface $store) {
    $store_registrations = $store->get('tax_registrations')->getValue();
    $store_registrations = array_column($store_registrations, 'value');
    return in_array('CA', $store_registrations);
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveZones(OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $customer_address = $customer_profile->get('address')->first();
    if ($customer_address->getCountryCode() != 'CA') {
      return [];
    }
    return parent::resolveZones($order_item, $customer_profile);
  }

  /**
   * {@inheritdoc}
   */
  public function buildZones() {
    $zones = [];

    $zones['ca'] = new TaxZone([
      'id' => 'ca',
      'label' => $this->t('- Federal -'),
      'display_label' => $this->t('GST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'AB'],
        ['country_code' => 'CA', 'administrative_area' => 'BC'],
        ['country_code' => 'CA', 'administrative_area' => 'MB'],
        ['country_code' => 'CA', 'administrative_area' => 'NT'],
        ['country_code' => 'CA', 'administrative_area' => 'NU'],
        ['country_code' => 'CA', 'administrative_area' => 'QC'],
        ['country_code' => 'CA', 'administrative_area' => 'SK'],
        ['country_code' => 'CA', 'administrative_area' => 'YT'],
      ],
      'rates' => [
        [
          'id' => 'gst',
          'label' => $this->t('GST'),
          'percentages' => [
            ['number' => '0.05', 'start_date' => '2008-01-01'],
          ],
          'default' => TRUE,
        ],
      ],
    ]);
    $zones['bc'] = new TaxZone([
      'id' => 'bc',
      'label' => $this->t('British Columbia'),
      'display_label' => $this->t('PST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'BC'],
      ],
      'rates' => [
        [
          'id' => 'pst',
          'label' => $this->t('PST'),
          'percentages' => [
            ['number' => '0.07', 'start_date' => '2013-04-01'],
          ],
        ],
      ],
    ]);
    $zones['mb'] = new TaxZone([
      'id' => 'mb',
      'label' => $this->t('Manitoba'),
      'display_label' => $this->t('PST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'MB'],
      ],
      'rates' => [
        [
          'id' => 'pst',
          'label' => $this->t('PST'),
          'percentages' => [
            ['number' => '0.08', 'start_date' => '2013-07-01', 'end_date' => '2023-06-30'],
          ],
        ],
      ],
    ]);
    $zones['nb'] = new TaxZone([
      'id' => 'nb',
      'label' => $this->t('New Brunswick'),
      'display_label' => $this->t('HST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'NB'],
      ],
      'rates' => [
        [
          'id' => 'hst',
          'label' => $this->t('HST'),
          'percentages' => [
            ['number' => '0.15', 'start_date' => '2016-07-01'],
          ],
        ],
      ],
    ]);
    $zones['nl'] = new TaxZone([
      'id' => 'nl',
      'label' => $this->t('Newfoundland'),
      'display_label' => $this->t('HST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'NL'],
      ],
      'rates' => [
        [
          'id' => 'hst',
          'label' => $this->t('HST'),
          'percentages' => [
            ['number' => '0.15', 'start_date' => '2016-07-01'],
          ],
        ],
      ],
    ]);
    $zones['ns'] = new TaxZone([
      'id' => 'ns',
      'label' => $this->t('Nova Scotia'),
      'display_label' => $this->t('HST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'NS'],
      ],
      'rates' => [
        [
          'id' => 'hst',
          'label' => $this->t('HST'),
          'percentages' => [
            ['number' => '0.15', 'start_date' => '2010-07-01'],
          ],
        ],
      ],
    ]);
    $zones['on'] = new TaxZone([
      'id' => 'on',
      'label' => $this->t('Ontario'),
      'display_label' => $this->t('HST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'ON'],
      ],
      'rates' => [
        [
          'id' => 'hst',
          'label' => $this->t('HST'),
          'percentages' => [
            ['number' => '0.13', 'start_date' => '2010-07-01'],
          ],
        ],
      ],
    ]);
    $zones['pe'] = new TaxZone([
      'id' => 'pe',
      'label' => $this->t('Prince Edward Island'),
      'display_label' => $this->t('HST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'PE'],
      ],
      'rates' => [
        [
          'id' => 'hst',
          'label' => $this->t('HST'),
          'percentages' => [
            ['number' => '0.14', 'start_date' => '2013-04-01', 'end_date' => '2016-09-30'],
            ['number' => '0.15', 'start_date' => '2016-10-01'],
          ],
        ],
      ],
    ]);
    $zones['qc'] = new TaxZone([
      'id' => 'qc',
      'label' => $this->t('Quebec'),
      'display_label' => $this->t('QST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'QC'],
      ],
      'rates' => [
        [
          'id' => 'qst',
          'label' => $this->t('QST'),
          'percentages' => [
            ['number' => '0.09975', 'start_date' => '2013-01-01'],
          ],
        ],
      ],
    ]);
    $zones['sk'] = new TaxZone([
      'id' => 'sk',
      'label' => $this->t('Saskatchewan'),
      'display_label' => $this->t('PST'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'SK'],
      ],
      'rates' => [
        [
          'id' => 'pst',
          'label' => $this->t('PST'),
          'percentages' => [
            ['number' => '0.05', 'start_date' => '2013-04-01', 'end_date' => '2017-03-22'],
            ['number' => '0.06', 'start_date' => '2017-03-23'],
          ],
        ],
      ],
    ]);

    return $zones;
  }

}
