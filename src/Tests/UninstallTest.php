<?php

namespace Drupal\commerce\Tests;

use Drupal\system\Tests\Module\ModuleTestBase;

/**
 * Tests module uninstallation.
 *
 * @group commerce
 */
class UninstallTest extends ModuleTestBase {

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
    // Confirm that all Commerce modules have been installed successfully.
    $this->assertModules(self::$modules, TRUE);

    // Uninstall all modules.
    $this->container->get('module_installer')->uninstall(self::$modules);
    $this->assertModules(self::$modules, FALSE);

    // Reinstall the modules. If there was no trailing configuration left
    // behind after uninstall, then this too should be successful.
    $this->container->get('module_installer')->install(self::$modules);
    $this->assertModules(self::$modules, TRUE);
  }

}
