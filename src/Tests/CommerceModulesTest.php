<?php

namespace Drupal\commerce\Tests;

use Drupal\system\Tests\Module\ModuleTestBase;

/**
 * Class CommerceModulesTest, covers testing of module reinstallations.
 *
 * @group commerce
 */
class CommerceModulesTest extends ModuleTestBase {

  /**
   * Enables all required modules, extending CommerceTestBase.
   */
  public static $modules = [
    'commerce',
    'commerce_tax',
    'commerce_price',
    'commerce_store',
    'commerce_order',
    'commerce_product',
    'commerce_cart',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'administer modules',
      'configure store',
    ];
  }

  /**
   * Tests the uninstall and reinstall of every module.
   */
  public function testUninstall() {
    // List of modules to check.
    $modules = [
      'commerce',
      'commerce_tax',
      'commerce_price',
      'commerce_store',
      'commerce_order',
      'commerce_product',
      'commerce_cart',
    ];

    // Check if all modules are installed.
    $this->assertModules($modules, TRUE);

    // Uninstall modules.
    $this->container->get('module_installer')->uninstall($modules);

    // Check if all uninstalled.
    $this->assertModules($modules, FALSE);

    // Revert the process by reinstalling.
    $this->container->get('module_installer')->install($modules);

    // Check if all uninstalled.
    $this->assertModules($modules, TRUE);
  }

}
