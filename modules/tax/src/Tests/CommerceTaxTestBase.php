<?php

/**
 * @file
 * Definition of \Drupal\commerce_tax\Tests\CommerceTaxTestBase.
 */

namespace Drupal\commerce_tax\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for shortcut test cases.
 */
abstract class CommerceTaxTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('commerce', 'commerce_tax');

  /**
   * User with permission to administer products.
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array(
      'administer stores',
      'access administration pages',
    ));
    $this->drupalLogin($this->adminUser);
  }
}
