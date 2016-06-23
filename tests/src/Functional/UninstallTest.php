<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group commerce
 */
class UninstallTest extends BrowserTestBase {

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
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayHasKey($module, $installed_modules, t('Commerce module @module installed successfully.', ['@module' => $module]));
    }

    // Uninstall all modules.
    $this->container->get('module_installer')->uninstall(self::$modules);
    // We need to rebuild the container to refresh our modules list.
    $this->container = $this->kernel->rebuildContainer();
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayNotHasKey($module, $installed_modules, t('Commerce module @module uninstalled successfully.', ['@module' => $module]));
    }

    // Reinstall the modules. If there was no trailing configuration left
    // behind after uninstall, then this too should be successful.
    $this->container->get('module_installer')->install(self::$modules);
    $this->container = $this->kernel->rebuildContainer();
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayHasKey($module, $installed_modules, t('Commerce module @module reinstalled successfully.', ['@module' => $module]));
    }
  }

}
