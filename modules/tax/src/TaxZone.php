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
      $this->rates[] = new TaxRate($rate_definition);
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
   *   The tax rates.
   */
  public function getRates() {
    return $this->rates;
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
