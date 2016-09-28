<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ConfigUpdater class.
 *
 * @group commerce
 */
class ConfigUpdaterTest extends KernelTestBase {

  /**
   * Enable modules.
   *
   * @var array
   */
  public static $modules = [
    'system', 'field', 'options', 'user', 'entity',
    'entity_reference_revisions', 'views', 'address', 'profile',
    'state_machine', 'inline_entity_form', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product', 'commerce_order',
    'commerce_update_test',
  ];

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

    $this->installSchema('system', 'router');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_update_test');

    $this->configUpdater = \Drupal::service('commerce.config_updater');
  }

  /**
   * Tests loading configuration from active storage.
   */
  public function testLoadFromActive() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $data = $this->configUpdater->loadFromActive($config_name);
    $this->assertEqual($data['id'], 'testing');
  }

  /**
   * Tests loading configuration from extension storage.
   */
  public function testLoadFromExtension() {
    $config_name = 'views.view.commerce_stores';
    $data = $this->configUpdater->loadFromExtension($config_name);
    $this->assertEqual($data['id'], 'commerce_stores');
  }

  /**
   * Tests checking whether configuration was modified.
   */
  public function testIsModified() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $config = $this->configUpdater->loadFromActive($config_name);
    $this->assertFalse($this->configUpdater->isModified($config));

    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')->load('testing');
    $store_type->setDescription('The default store');
    $store_type->save();

    $config = $this->configUpdater->loadFromActive($config_name);
    $this->assertTrue($this->configUpdater->isModified($config));
  }

  /**
   * Tests importing configuration.
   */
  public function testImport() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $this->configUpdater->delete([$config_name]);

    $result = $this->configUpdater->import([$config_name]);
    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($failed));
    $this->assertEqual($succeeded[$config_name], "$config_name was successfully imported");

    $result = $this->configUpdater->import([$config_name]);
    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($succeeded));
    $this->assertEqual($failed[$config_name], "$config_name already exists, use revert to update");
  }

  /**
   * Tests reverting configuration.
   */
  public function testRevert() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')->load('testing');
    $store_type->setDescription('The default store');
    $store_type->save();

    $result = $this->configUpdater->revert([$config_name]);
    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($succeeded));
    $this->assertEqual($failed[$config_name], "$config_name could not be reverted because it was modified by the user");

    $result = $this->configUpdater->revert([$config_name], FALSE);
    $succeeded = $result->getSucceeded();

    $this->assertFalse(empty($succeeded));
    $this->assertEqual($succeeded[$config_name], "$config_name was successfully reverted");

    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = \Drupal::entityTypeManager()->getStorage('commerce_store_type')->load('testing');
    $this->assertNull($store_type->getDescription());
  }

  /**
   * Tests deleting configuration.
   */
  public function testDelete() {
    $config_name = 'commerce_store.commerce_store_type.testing';
    $result = $this->configUpdater->delete([$config_name]);
    $failed = $result->getFailed();
    $succeeded = $result->getSucceeded();

    $this->assertTrue(empty($failed));
    $this->assertEqual($succeeded[$config_name], "$config_name was successfully deleted");
  }

}
