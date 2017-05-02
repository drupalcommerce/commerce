<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides the Canadian tax type.
 *
 * @CommerceTaxType(
 *   id = "canadian_sales_tax",
 *   label = "Canadian Sales Tax",
 * )
 */
class CanadianSalesTax extends LocalTaxTypeBase {

  /**
   * {@inheritdoc}
   */
  public function shouldRound() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->t('Canadian Sales Tax');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    $store = $order->getStore();
    if ($store->getAddress()->getCountryCode() == 'CA') {
      return TRUE;
    }
    $store_registrations = $store->get('tax_registrations')->getValue();
    $store_registrations = array_column($store_registrations, 'value');
    if (array_intersect($store_registrations, ['CA'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getZones() {
    $zones = [];

    $zones['ca'] = new TaxZone([
      'id' => 'ca',
      'label' => $this->t('Canadian GST'),
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
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2008-01-01'],
          ],
          'default' => TRUE,
        ],
      ],
    ]);

    $zones['bc'] = new TaxZone([
      'id' => 'bc',
      'label' => $this->t('British Columbia'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'BC'],
      ],
      'rates' => [
        [
          'id' => 'PST',
          'label' => $this->t('PST'),
          'amounts' => [
            ['amount' => '0.07', 'start_date' => '2013-04-01'],
          ],
        ],
      ],
    ]);

    $zones['mb'] = new TaxZone([
      'id' => 'mb',
      'label' => $this->t('Manitoba'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'MB'],
      ],
      'rates' => [
        [
          'id' => 'PST',
          'label' => $this->t('PST'),
          'amounts' => [
            ['amount' => '0.08', 'start_date' => '2013-07-01', 'end_date' => '2023-06-30'],
          ],
        ],
      ],
    ]);

    $zones['nb'] = new TaxZone([
      'id' => 'nb',
      'label' => $this->t('New Brunswick'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'NB'],
      ],
      'rates' => [
        [
          'id' => 'HST',
          'label' => $this->t('HST'),
          'amounts' => [
            ['amount' => '0.15', 'start_date' => '2016-07-01'],
          ],
        ],
      ],
    ]);

    $zones['nl'] = new TaxZone([
      'id' => 'nl',
      'label' => $this->t('Newfoundland'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'NL'],
      ],
      'rates' => [
        [
          'id' => 'HST',
          'label' => $this->t('HST'),
          'amounts' => [
            ['amount' => '0.15', 'start_date' => '2016-07-01'],
          ],
        ],
      ],
    ]);

    $zones['ns'] = new TaxZone([
      'id' => 'ns',
      'label' => $this->t('Nova Scotia'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'NS'],
      ],
      'rates' => [
        [
          'id' => 'HST',
          'label' => $this->t('HST'),
          'amounts' => [
            ['amount' => '0.15', 'start_date' => '2010-07-01'],
          ],
        ],
      ],
    ]);

    $zones['on'] = new TaxZone([
      'id' => 'on',
      'label' => $this->t('Ontario'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'ON'],
      ],
      'rates' => [
        [
          'id' => 'HST',
          'label' => $this->t('HST'),
          'amounts' => [
            ['amount' => '0.13', 'start_date' => '2010-07-01'],
          ],
        ],
      ],
    ]);

    $zones['pe'] = new TaxZone([
      'id' => 'pe',
      'label' => $this->t('PEI'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'PE'],
      ],
      'rates' => [
        [
          'id' => 'HST',
          'label' => $this->t('HST'),
          'amounts' => [
            ['amount' => '0.14', 'start_date' => '2013-04-01'],
          ],
        ],
      ],
    ]);

    $zones['qc'] = new TaxZone([
      'id' => 'qc',
      'label' => $this->t('Quebec'),
      'territories' => [
        ['country_code' => 'CA', 'administrative_area' => 'QC'],
      ],
      'rates' => [
        [
          'id' => 'QST',
          'label' => $this->t('QST'),
          'amounts' => [
            ['amount' => '0.09975', 'start_date' => '2013-01-01'],
          ],
        ],
      ],
    ]);

    return $zones;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveZones(OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $customer_address = $customer_profile->address->first();
    if ($customer_address->getCountryCode() != 'CA') {
      return [];
    }

    $zones = $this->getZones();
    $customer_zones = array_filter($zones, function ($zone) use ($customer_address) {
      /** @var \Drupal\commerce_tax\TaxZone $zone */
      return $zone->match($customer_address);
    });

    return $customer_zones;
  }

}
