<?php

namespace Drupal\commerce_tax_test\EventSubscriber;

use Drupal\commerce_tax\Event\BuildZonesEvent;
use Drupal\commerce_tax\Event\TaxEvents;
use Drupal\commerce_tax\TaxZone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a test event subscriber that alters the Germany tax rates.
 */
class BuildZonesEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      TaxEvents::BUILD_ZONES => 'onBuildZones',
    ];
  }

  /**
   * Alters the Germany tax rates.
   *
   * @param \Drupal\commerce_tax\Event\BuildZonesEvent $event
   *   The build zones event.
   */
  public function onBuildZones(BuildZonesEvent $event) {
    $plugin = $event->getPlugin();
    if ($plugin->getPluginId() !== 'european_union_vat') {
      return;
    }
    $zones = $event->getZones();
    $germany_zone = $zones['de']->toArray();
    // Add a "fake" standard rate percentage of 25% from January 1st, 2041.
    foreach ($germany_zone['rates'] as &$rate) {
      if ($rate['id'] !== 'standard') {
        continue;
      }
      $rate['percentages'][] = ['number' => '0.25', 'start_date' => '2041-01-01'];
    }
    $zones['de'] = new TaxZone($germany_zone);
    $event->setZones($zones);
  }

}
