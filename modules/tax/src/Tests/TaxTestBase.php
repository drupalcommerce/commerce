<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Tests\TaxTestBase.
 */

namespace Drupal\commerce_tax\Tests;

use Drupal\commerce\Tests\CommerceTestBase;

/**
 * Defines the base class for tax test cases.
 */
abstract class TaxTestBase extends CommerceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['commerce', 'commerce_tax', 'commerce_product'];

  /**
   * {@inheritdoc}
   */
  protected function defaultAdminUserPermissions() {
    return [
      'view the administration theme',
      'configure store',
      'administer stores',
      'access administration pages',
    ];
  }
}
