<?php

namespace Drupal\commerce_tax\Event;

use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the build zones event.
 *
 * @see \Drupal\commerce_tax\Event\TaxEvents
 */
class BuildZonesEvent extends Event {

  /**
   * The tax zones.
   *
   * @var \Drupal\commerce_tax\TaxZone[]
   */
  protected $zones;

  /**
   * The tax type plugin.
   *
   * @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface
   */
  protected $plugin;

  /**
   * Constructs a new BuildZonesEvent.
   *
   * @param \Drupal\commerce_tax\TaxZone[] $zones
   *   The tax zones.
   * @param \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $plugin
   *   The local tax type plugin.
   */
  public function __construct($zones, LocalTaxTypeInterface $plugin) {
    $this->zones = $zones;
    $this->plugin = $plugin;
  }

  /**
   * Gets the tax zones.
   *
   * @return \Drupal\commerce_tax\TaxZone[]
   *   The tax zones.
   */
  public function getZones() {
    return $this->zones;
  }

  /**
   * Sets the tax zones.
   *
   * @param \Drupal\commerce_tax\TaxZone[] $zones
   *   The tax zones.
   *
   * @return $this
   */
  public function setZones($zones) {
    $this->zones = $zones;
    return $this;
  }

  /**
   * Gets the local tax type plugin.
   *
   * @return \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface
   *   The tax type plugin.
   */
  public function getPlugin() {
    return $this->plugin;
  }

}
