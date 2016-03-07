<?php

namespace Drupal\commerce\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the ConfigUpdater class.
 *
 * @group commerce
 */
class ConfigUpdaterTest extends WebTestBase {
  public static $modules = ['commerce_update_test'];

  /**
   * The config updater service.
   *
   * @var \Drupal\commerce\Config\ConfigUpdaterInterface
   */
  protected $configUpdater;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configUpdater = \Drupal::service('commerce.config_updater');
  }

  /**
   * Tests loading data from active storage.
   */
  public function testLoadFromActive() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $data = $this->configUpdater->loadFromActive($config_name);
    $this->assertEqual($data['id'], 'testing');
  }

  /**
   * Tests loading data from extension storage.
   */
  public function testLoadFromExtension() {
    $config_name = 'views.view.commerce_stores';
    $data = $this->configUpdater->loadFromExtension($config_name);
    $this->assertEqual($data['id'], 'commerce_stores');
  }

  /**
   * Test isModified on the commerce_store_type.default config object.
   */
  public function testIsModified() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $this->assertFalse($this->configUpdater->isModified($config_name));

    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')
      ->load('testing');
    $store_type->setDescription('The default store');
    $store_type->save();

    $this->assertTrue($this->configUpdater->isModified($config_name));
  }

  /**
   * Tests delete when config is not modified.
   */
  public function testDeleteNotModified() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $result = $this->configUpdater->delete([
      $config_name,
    ]);

    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($failed));
    $this->assertEqual($succeeded[$config_name], "$config_name was successfully deleted");
  }

  /**
   * Tests delete when config is not modified.
   */
  public function testDeleteModified() {
    $config_name = 'commerce_store.commerce_store_type.testing';

    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')
                         ->load('testing');
    $store_type->setDescription('The default store');
    $store_type->save();

    $result = $this->configUpdater->delete([
      $config_name,
    ]);

    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($succeeded));
    $this->assertEqual($failed[$config_name], "$config_name has been modified and was not deleted");
  }

  /**
   * Tests revert if config is modified, and forced revert.
   */
  public function testRevertModified() {
    $config_name = 'commerce_store.commerce_store_type.testing';

    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')
                         ->load('testing');
    $store_type->setDescription('The default store');
    $store_type->save();

    $result = $this->configUpdater->revert([
      $config_name,
    ]);

    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($succeeded));
    $this->assertEqual($failed[$config_name], "$config_name has been modified and was not reverted");

    $result = $this->configUpdater->revert([
      $config_name,
    ], FALSE);

    $succeeded = $result->getSucceeded();

    $this->assertFalse(empty($succeeded));
    $this->assertEqual($succeeded[$config_name], "$config_name was successfully reverted");

    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')
                         ->load('testing');
    $this->assertNull($store_type->getDescription());
  }

  /**
   * Tests import.
   */
  public function testImport() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $this->configUpdater->delete([$config_name]);

    $result = $this->configUpdater->import([
      $config_name,
    ]);

    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($failed));
    $this->assertEqual($succeeded[$config_name], "$config_name was successfully imported");

    $result = $this->configUpdater->import([
      $config_name,
    ]);

    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($succeeded));
    $this->assertEqual($failed[$config_name], "$config_name already exists, use revert to update");
  }

}
