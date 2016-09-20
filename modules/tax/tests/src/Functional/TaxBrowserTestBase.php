<?php

namespace Drupal\Tests\commerce_tax\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Defines the base class for commerce_tax test cases.
 */
abstract class TaxBrowserTestBase extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_store',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce stores',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Creates a new zone entity.
   *
   * Initialize a base zone.
   *
   * @return \Drupal\address\Entity\Zone
   *   A new zone entity.
   */
  public function createZone() {
    $random = strtolower($this->randomMachineName(5));
    // Creates a Zone.
    $values = [
      'name' => $random,
      'id' => $random,
    ];
    return $this->createEntity('zone', $values);
  }

}
