<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the bundle plugin API.
 *
 * @group commmerce
 */
class BundlePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'commerce', 'commerce_plugin_bundles_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('entity_test_plugin_bundle');
    $this->installConfig('commerce_plugin_bundles_test');
  }

  /**
   * Tests the bundle plugins.
   */
  public function testPluginBundles() {
    $bundled_entity_types = commerce_get_bundle_plugin_entity_types();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = reset($bundled_entity_types);
    $this->assertEquals('entity_test_plugin_bundle', $entity_type->id());
    $this->assertTrue($entity_type->hasHandlerClass('bundle_plugin'));

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info */
    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundle_info = $entity_type_bundle_info->getBundleInfo('entity_test_plugin_bundle');
    $this->assertEquals(2, count($bundle_info));
    $this->assertTrue(isset($bundle_info['entity_test_plugin_bundle']['test1']));
    $this->assertTrue(isset($bundle_info['entity_test_plugin_bundle']['test3']));
  }

}
