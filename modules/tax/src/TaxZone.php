<?php

namespace Drupal\commerce_tax;

use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Zone\ZoneTerritory;

/**
 * Represents a tax zone.
 */
class TaxZone {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The display label.
   *
   * @var string
   */
  protected $displayLabel;

  /**
   * The territories.
   *
   * @var \CommerceGuys\Addressing\Zone\ZoneTerritory[]
   */
  protected $territories;

  /**
   * The tax rates.
   *
   * @var \Drupal\commerce_tax\TaxRate[]
   */
  protected $rates;

  /**
   * Constructs a new TaxZone instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'display_label', 'territories', 'rates'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    foreach (['territories', 'rates'] as $property) {
      if (!is_array($definition[$property])) {
        throw new \InvalidArgumentException(sprintf('The property "%s" must be an array.', $property));
      }
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->displayLabel = $definition['display_label'];
    foreach ($definition['territories'] as $territory_definition) {
      $this->territories[] = new ZoneTerritory($territory_definition);
    }
    foreach ($definition['rates'] as $rate_definition) {
      $tax_rate = new TaxRate($rate_definition);
      $this->rates[$tax_rate->getId()] = $tax_rate;
    }
  }

  /**
   * Gets the ID.
   *
   * @return string
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the display label.
   *
   * Used to identify the applied tax in order summaries.
   *
   * @return string
   *   The display label.
   */
  public function getDisplayLabel() {
    return $this->displayLabel;
  }

  /**
   * Gets the territories.
   *
   * @return \CommerceGuys\Addressing\Zone\ZoneTerritory[]
   *   The territories.
   */
  public function getTerritories() {
    return $this->territories;
  }

  /**
   * Gets the tax rates.
   *
   * @return \Drupal\commerce_tax\TaxRate[]
   *   The tax rates, keyed by ID.
   */
  public function getRates() {
    return $this->rates;
  }

  /**
   * Gets the array representation of the tax zone.
   *
   * @return array
   *   The array representation of the tax zone.
   */
  public function toArray() : array {
    return [
      'id' => $this->id,
      'label' => $this->label,
      'display_label' => $this->displayLabel,
      'territories' => array_map(function (ZoneTerritory $territory) {
        // This logic would be best suited in the addressing library.
        return array_filter([
          'country_code' => $territory->getCountryCode(),
          'administrative_area' => $territory->getAdministrativeArea(),
          'locality' => $territory->getLocality(),
          'dependent_locality' => $territory->getDependentLocality(),
          'included_postal_codes' => $territory->getIncludedPostalCodes(),
          'excluded_postal_codes' => $territory->getExcludedPostalCodes(),
        ]);
      }, $this->territories),
      'rates' => array_map(function (TaxRate $rate) {
        return $rate->toArray();
      }, array_values($this->rates)),
    ];
  }

  /**
   * Gets the tax rate with the given ID.
   *
   * @param string $rate_id
   *   The tax rate ID.
   *
   * @return \Drupal\commerce_tax\TaxRate|null
   *   The tax rate, or NULL if none found.
   */
  public function getRate($rate_id) {
    return isset($this->rates[$rate_id]) ? $this->rates[$rate_id] : NULL;
  }

  /**
   * Gets the default tax rate.
   *
   * @return \Drupal\commerce_tax\TaxRate
   *   The default rate.
   */
  public function getDefaultRate() {
    $default_rate = reset($this->rates);
    foreach ($this->rates as $rate) {
      if ($rate->isDefault()) {
        $default_rate = $rate;
        break;
      }
    }

    return $default_rate;
  }

  /**
   * Checks whether the given address belongs to the zone.
   *
   * @param \CommerceGuys\Addressing\AddressInterface $address
   *   The address.
   *
   * @return bool
   *   TRUE if the address belongs to the zone, FALSE otherwise.
   */
  public function match(AddressInterface $address) {
    foreach ($this->territories as $territory) {
      if ($territory->match($address)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
