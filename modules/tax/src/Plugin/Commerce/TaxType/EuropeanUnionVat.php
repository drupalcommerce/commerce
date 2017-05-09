<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\TaxableType;
use Drupal\commerce_tax\TaxZone;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides the European Union VAT tax type.
 *
 * @CommerceTaxType(
 *   id = "european_union_vat",
 *   label = "European Union VAT",
 * )
 */
class EuropeanUnionVat extends LocalTaxTypeBase {

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
  public function getDisplayLabel() {
    return $this->t('VAT');
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRound() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveZones(OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $zones = $this->getZones();
    $customer_address = $customer_profile->address->first();
    $customer_country = $customer_address->getCountryCode();
    $customer_zones = array_filter($zones, function ($zone) use ($customer_address) {
      /** @var \Drupal\commerce_tax\TaxZone $zone */
      return $zone->match($customer_address);
    });
    if (empty($customer_zones)) {
      // The customer is not in the EU.
      return [];
    }
    $order = $order_item->getOrder();
    $store = $order->getStore();
    $store_address = $store->getAddress();
    $store_country = $store_address->getCountryCode();
    $store_zones = array_filter($zones, function ($zone) use ($store_address) {
      /** @var \Drupal\commerce_tax\TaxZone $zone */
      return $zone->match($store_address);
    });
    $store_registration_zones = array_filter($zones, function ($zone) use ($store) {
      /** @var \Drupal\commerce_tax\TaxZone $zone */
      return $this->checkRegistrations($store, $zone);
    });

    // @todo Replace with $customer_profile->get('tax_number')->value
    // once tax numbers are implemented.
    $customer_tax_number = '';
    // Since january 1st 2015 all digital goods sold to EU customers
    // must use the customer zone. For example, an ebook sold
    // to Germany needs to have German VAT applied.
    $taxable_type = $this->getTaxableType($order_item);
    $year = $this->getCalculationDate($order)->format('Y');
    $is_digital = $taxable_type == TaxableType::DIGITAL_GOODS && $year >= '2015';
    $resolved_zones = [];
    if (empty($store_zones) && !empty($store_registration_zones)) {
      // The store is not in the EU but is registered to collect VAT.
      // This VAT is only charged on B2C digital services.
      $resolved_zones = [];
      if ($is_digital && !$customer_tax_number) {
        $resolved_zones = $customer_zones;
      }
    }
    elseif ($customer_tax_number && $customer_country != $store_country) {
      // Intra-community supply (B2B).
      $ic_zone = $this->getIcZone();
      $resolved_zones = [$ic_zone];
    }
    elseif ($is_digital) {
      $resolved_zones = $customer_zones;
    }
    else {
      // Physical products use the origin zone, unless the store is
      // registered to pay taxes in the destination zone. This is required
      // when the total yearly transactions breach the defined threshold.
      // See http://www.vatlive.com/eu-vat-rules/vat-registration-threshold/
      $resolved_zones = $store_zones;
      $customer_zone = reset($customer_zones);
      if ($this->checkRegistrations($store, $customer_zone)) {
        $resolved_zones = $customer_zones;
      }
    }

    return $resolved_zones;
  }

  /**
   * {@inheritdoc}
   */
  public function getZones() {
    $zones = [];
    $zones['at'] = new TaxZone([
      'id' => 'at',
      'label' => $this->t('Austria'),
      'territories' => [
        // Austria without Jungholz and Mittelberg.
        ['country_code' => 'AT', 'excluded_postal_codes' => '6691, 6991:6993'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '1995-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.13', 'start_date' => '2016-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '1995-01-01'],
          ],
        ],
      ],
    ]);
    $zones['be'] = new TaxZone([
      'id' => 'be',
      'label' => $this->t('Belgium'),
      'territories' => [
        ['country_code' => 'BE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.21', 'start_date' => '1996-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.12', 'start_date' => '1992-04-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.06', 'start_date' => '1971-01-01'],
          ],
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '1971-01-01'],
          ],
        ],
      ],
    ]);
    $zones['bg'] = new TaxZone([
      'id' => 'bg',
      'label' => $this->t('Bulgaria'),
      'territories' => [
        ['country_code' => 'BG'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '2007-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.09', 'start_date' => '2011-04-01'],
          ],
        ],
      ],
    ]);
    $zones['cy'] = new TaxZone([
      'id' => 'cy',
      'label' => $this->t('Cyprus'),
      'territories' => [
        ['country_code' => 'CY'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.19', 'start_date' => '2014-01-13'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.09', 'start_date' => '2014-01-13'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2004-05-01'],
          ],
        ],
      ],
    ]);
    $zones['cz'] = new TaxZone([
      'id' => 'cz',
      'label' => $this->t('Czech Republic'),
      'territories' => [
        ['country_code' => 'CZ'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.21', 'start_date' => '2013-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.15', 'start_date' => '2013-01-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '2015-01-01'],
          ],
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '2004-05-01'],
          ],
        ],
      ],
    ]);
    $zones['de'] = new TaxZone([
      'id' => 'de',
      'label' => $this->t('Germany'),
      'territories' => [
        // Germany without Heligoland and Büsingen.
        ['country_code' => 'DE', 'excluded_postal_codes' => '27498, 78266'],
        // Austria (Jungholz and Mittelberg).
        ['country_code' => 'AT', 'excluded_postal_codes' => '6691, 6991:6993'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.19', 'start_date' => '2007-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.07', 'start_date' => '1983-07-01'],
          ],
        ],
      ],
    ]);
    $zones['dk'] = new TaxZone([
      'id' => 'dk',
      'label' => $this->t('Denmark'),
      'territories' => [
        ['country_code' => 'DK'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.25', 'start_date' => '1992-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '1973-01-01'],
          ],
        ],
      ],
    ]);
    $zones['ee'] = new TaxZone([
      'id' => 'ee',
      'label' => $this->t('Estonia'),
      'territories' => [
        ['country_code' => 'EE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '2009-07-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.09', 'start_date' => '2009-01-01'],
          ],
        ],
      ],
    ]);
    $zones['es'] = new TaxZone([
      'id' => 'es',
      'label' => $this->t('Spain'),
      'territories' => [
        // Spain without Canary Islands, Ceuta and Melilla.
        ['country_code' => 'ES', 'excluded_postal_codes' => '/(35|38|51|52)[0-9]{3}/'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.21', 'start_date' => '2012-09-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '2012-09-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.04', 'start_date' => '1995-01-01'],
          ],
        ],
      ],
    ]);
    $zones['fi'] = new TaxZone([
      'id' => 'fi',
      'label' => $this->t('Finland'),
      'territories' => [
        // Finland without Åland Islands.
        ['country_code' => 'FI', 'excluded_postal_codes' => '22000:22999'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.24', 'start_date' => '2013-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.14', 'start_date' => '2013-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '2013-01-01'],
          ],
        ],
      ],
    ]);
    $zones['fr'] = new TaxZone([
      'id' => 'fr',
      'label' => $this->t('France'),
      'territories' => [
        // France without Corsica.
        ['country_code' => 'FR', 'excluded_postal_codes' => '/(20)[0-9]{3}/'],
        ['country_code' => 'MC'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '2014-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '2014-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.055', 'start_date' => '1982-07-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.021', 'start_date' => '1986-07-01'],
          ],
        ],
      ],
    ]);
    $zones['fr_h'] = new TaxZone([
      'id' => 'fr_h',
      'label' => $this->t('France (Corsica)'),
      'territories' => [
        // France without Corsica.
        ['country_code' => 'FR', 'included_postal_codes' => '/(20)[0-9]{3}/'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '2014-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'special',
          'label' => $this->t('Special'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '2014-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.021', 'start_date' => '1997-09-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.009', 'start_date' => '1972-04-01'],
          ],
        ],
      ],
    ]);
    $zones['gb'] = new TaxZone([
      'id' => 'gb',
      'label' => $this->t('Great Britain'),
      'territories' => [
        ['country_code' => 'GB'],
        ['country_code' => 'IM'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '2011-01-04'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '1997-09-01'],
          ],
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '1973-01-01'],
          ],
        ],
      ],
    ]);
    $zones['gr'] = new TaxZone([
      'id' => 'gr',
      'label' => $this->t('Greece'),
      'territories' => [
        // Greece without Thassos, Samothrace, Skiros, Northern Sporades, Lesbos, Chios, The Cyclades, The Dodecanese.
        ['country_code' => 'GR', 'excluded_postal_codes' => '/640 ?04|680 ?02|340 ?07|((370|811|821|840|851) ?[0-9]{2})/'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.23', 'start_date' => '2010-07-01', 'end_date' => '2015-05-31'],
            ['amount' => '0.24', 'start_date' => '2016-06-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.13', 'start_date' => '2011-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.06', 'start_date' => '2015-07-01'],
          ],
        ],
      ],
    ]);
    $zones['hr'] = new TaxZone([
      'id' => 'hr',
      'label' => $this->t('Croatia'),
      'territories' => [
        ['country_code' => 'HR'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.25', 'start_date' => '2013-07-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.13', 'start_date' => '2014-01-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2014-01-01'],
          ],
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '2013-07-01'],
          ],
        ],
      ],
    ]);
    $zones['hu'] = new TaxZone([
      'id' => 'hu',
      'label' => $this->t('Hungary'),
      'territories' => [
        ['country_code' => 'HU'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.27', 'start_date' => '2012-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.18', 'start_date' => '2009-07-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2004-05-01'],
          ],
        ],
      ],
    ]);
    $zones['ie'] = new TaxZone([
      'id' => 'ie',
      'label' => $this->t('Ireland'),
      'territories' => [
        ['country_code' => 'IE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.23', 'start_date' => '2012-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.135', 'start_date' => '2003-01-01'],
          ],
        ],
        [
          'id' => 'second_reduced',
          'label' => $this->t('Second Reduced'),
          'amounts' => [
            ['amount' => '0.09', 'start_date' => '2011-07-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.048', 'start_date' => '2005-01-01'],
          ],
        ],
        [
          'id' => 'zero',
          'label' => $this->t('Zero'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '1972-04-01'],
          ],
        ],
      ],
    ]);
    $zones['it'] = new TaxZone([
      'id' => 'it',
      'label' => $this->t('Italy'),
      'territories' => [
        // Italy without Livigno, Campione d’Italia and Lake Lugano.
        ['country_code' => 'IT', 'excluded_postal_codes' => '23030, 22060'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.22', 'start_date' => '2013-10-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '1995-02-24'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.04', 'start_date' => '1989-01-01'],
          ],
        ],
      ],
    ]);
    $zones['lt'] = new TaxZone([
      'id' => 'lt',
      'label' => $this->t('Lithuania'),
      'territories' => [
        ['country_code' => 'LT'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.21', 'start_date' => '2009-09-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.09', 'start_date' => '2004-05-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2004-05-01'],
          ],
        ],
      ],
    ]);
    $zones['lu'] = new TaxZone([
      'id' => 'lu',
      'label' => $this->t('Luxembourg'),
      'territories' => [
        ['country_code' => 'LU'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.17', 'start_date' => '2015-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.14', 'start_date' => '2015-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.08', 'start_date' => '2015-01-01'],
          ],
        ],
        [
          'id' => 'super_reduced',
          'label' => $this->t('Super Reduced'),
          'amounts' => [
            ['amount' => '0.03', 'start_date' => '1983-07-01'],
          ],
        ],
      ],
    ]);
    $zones['lv'] = new TaxZone([
      'id' => 'lv',
      'label' => $this->t('Latvia'),
      'territories' => [
        ['country_code' => 'LV'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.21', 'start_date' => '2012-07-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.12', 'start_date' => '2011-01-01'],
          ],
        ],
      ],
    ]);
    $zones['mt'] = new TaxZone([
      'id' => 'mt',
      'label' => $this->t('Malta'),
      'territories' => [
        ['country_code' => 'MT'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.18', 'start_date' => '2004-05-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.07', 'start_date' => '2011-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2004-05-01'],
          ],
        ],
      ],
    ]);
    $zones['nl'] = new TaxZone([
      'id' => 'nl',
      'label' => $this->t('Netherlands'),
      'territories' => [
        ['country_code' => 'NL'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.21', 'start_date' => '2012-10-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.06', 'start_date' => '1986-10-01'],
          ],
        ],
      ],
    ]);
    $zones['pl'] = new TaxZone([
      'id' => 'pl',
      'label' => $this->t('Poland'),
      'territories' => [
        ['country_code' => 'PL'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.22', 'start_date' => '2016-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.08', 'start_date' => '2011-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2011-01-01'],
          ],
        ],
      ],
    ]);
    $zones['pt'] = new TaxZone([
      'id' => 'pt',
      'label' => $this->t('Portugal'),
      'territories' => [
        // Portugal without Azores and Madeira.
        ['country_code' => 'PT', 'excluded_postal_codes' => '/(9)[0-9]{3}-[0-9]{3}/'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.23', 'start_date' => '2011-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.13', 'start_date' => '2010-07-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.06', 'start_date' => '2010-07-01'],
          ],
        ],
      ],
    ]);
    $zones['pt_30'] = new TaxZone([
      'id' => 'pt_30',
      'label' => $this->t('Portugal (Madeira)'),
      'territories' => [
        ['country_code' => 'PT', 'included_postal_codes' => '/(9)[5-9][0-9]{2}-[0-9]{3}/'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.22', 'start_date' => '2012-04-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.12', 'start_date' => '2012-04-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2012-04-01'],
          ],
        ],
      ],
    ]);
    $zones['ro'] = new TaxZone([
      'id' => 'ro',
      'label' => $this->t('Romania'),
      'territories' => [
        ['country_code' => 'RO'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.20', 'start_date' => '2016-01-01', 'end_date' => '2016-12-31'],
            ['amount' => '0.19', 'start_date' => '2017-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.09', 'start_date' => '2008-12-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.05', 'start_date' => '2008-12-01'],
          ],
        ],
      ],
    ]);
    $zones['se'] = new TaxZone([
      'id' => 'se',
      'label' => $this->t('Sweden'),
      'territories' => [
        ['country_code' => 'SE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.25', 'start_date' => '1995-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'intermediate',
          'label' => $this->t('Intermediate'),
          'amounts' => [
            ['amount' => '0.12', 'start_date' => '1995-01-01'],
          ],
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.06', 'start_date' => '1996-01-01'],
          ],
        ],
      ],
    ]);
    $zones['si'] = new TaxZone([
      'id' => 'si',
      'label' => $this->t('Slovenia'),
      'territories' => [
        ['country_code' => 'SI'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.22', 'start_date' => '2013-07-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.095', 'start_date' => '2013-07-01'],
          ],
        ],
      ],
    ]);
    $zones['sk'] = new TaxZone([
      'id' => 'sk',
      'label' => $this->t('Slovakia'),
      'territories' => [
        ['country_code' => 'SE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => $this->t('Standard'),
          'amounts' => [
            ['amount' => '0.2', 'start_date' => '2011-01-01'],
          ],
          'default' => TRUE,
        ],
        [
          'id' => 'reduced',
          'label' => $this->t('Reduced'),
          'amounts' => [
            ['amount' => '0.1', 'start_date' => '2011-01-01'],
          ],
        ],
      ],
    ]);

    return $zones;
  }

  /**
   * Gets the special Intra-Community Supply tax zone.
   *
   * Used by resolveZones() for cross-country B2B sales.
   *
   * @return \Drupal\commerce_tax\TaxZone
   *   The tax zone.
   */
  protected function getIcZone() {
    return new TaxZone([
      'id' => 'ic',
      'label' => $this->t('Intra-Community Supply'),
      'territories' => [
        // This territory won't match, but it doesn't need to.
        ['country_code' => 'EU'],
      ],
      'rates' => [
        [
          'id' => 'ic',
          'label' => $this->t('Intra-Community Supply'),
          'amounts' => [
            ['amount' => '0', 'start_date' => '1970-01-01'],
          ],
          'default' => TRUE,
        ],
      ],
    ]);
  }

}
