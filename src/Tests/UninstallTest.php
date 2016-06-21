<?php

namespace Drupal\commerce\Tests;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests module uninstallation.
 *
 * @group commerce
 */
class UninstallTest extends KernelTestBase {

  /**
   * Enables all required modules.
   */
  public static $modules = [
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_tax',
  ];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Uninstall all modules. Throws an exception if we are trying to disable
    // a module that is already disabled or if a module does not get disabled.
    $this->disableModules(self::$modules);
    // Enable all modules.
    $this->enableModules(self::$modules);
  }

}
