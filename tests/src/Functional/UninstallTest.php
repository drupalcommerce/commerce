<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\commerce\Traits\DeprecationSuppressionTrait;

/**
 * Tests module uninstallation.
 *
 * @group commerce
 */
class UninstallTest extends BrowserTestBase {

  use DeprecationSuppressionTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->setErrorHandler();
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $this->restoreErrorHandler();
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // The list doesn't include commerce_tax, which cannot be uninstalled due
    // to a core bug (#2871486).
    'commerce',
    'commerce_price',
    'commerce_log',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that all Commerce modules have been installed successfully.
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayHasKey($module, $installed_modules, t('Commerce module @module installed successfully.', ['@module' => $module]));
    }

    // Uninstall all modules except the base module.
    $modules = array_slice(self::$modules, 1);
    $this->container->get('module_installer')->uninstall($modules);
    $this->rebuildContainer();
    // Purge field data in order to remove the commerce_remote_id field.
    field_purge_batch(50);
    // Uninstall the base module.
    $this->container->get('module_installer')->uninstall(['commerce']);
    $this->rebuildContainer();
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayNotHasKey($module, $installed_modules, t('Commerce module @module uninstalled successfully.', ['@module' => $module]));
    }

    // Reinstall the modules. If there was no trailing configuration left
    // behind after uninstall, then this too should be successful.
    $this->container->get('module_installer')->install(self::$modules);
    $this->rebuildContainer();
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayHasKey($module, $installed_modules, t('Commerce module @module reinstalled successfully.', ['@module' => $module]));
    }
  }

}
