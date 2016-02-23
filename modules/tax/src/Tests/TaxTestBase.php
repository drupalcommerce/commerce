<?php

namespace Drupal\commerce_tax\Tests;

use Drupal\commerce\Tests\CommerceTestBase;

/**
 * Defines the base class for commerce_tax test cases.
 */
abstract class TaxTestBase extends CommerceTestBase {

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
      'administer stores',
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
