<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the plugin bundles capability.
 *
 * @group commmerce
 */
class BundlePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system','commerce', 'commerce_plugin_bundles_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('commerce_bundle_test_entity');
    $this->installConfig('commerce_plugin_bundles_test');
  }

  public function testPluginBundles() {
    $bundled_entity_types = commerce_get_bundle_plugin_entity_types();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = reset($bundled_entity_types);
    $this->assertEquals('commerce_bundle_test_entity', $entity_type->id());
    $this->assertTrue($entity_type->hasHandlerClass('bundle_plugin'));


    $bundle_info = commerce_entity_bundle_info();
    $this->assertTrue(isset($bundle_info['commerce_bundle_test_entity']['test1']));
    $this->assertTrue(isset($bundle_info['commerce_bundle_test_entity']['test3']));



    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info */
    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');

    $this->assertEquals(2, count($entity_type_bundle_info->getBundleInfo('commerce_bundle_test_entity')));
  }

}
