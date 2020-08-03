<?php

namespace Drupal\Tests\commerce_order\Kernel\Plugin\Commerce\Condition;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the purchased entity order conditions.
 *
 * @group commerce
 */
class PurchasedEntityConditionTest extends OrderKernelTestBase {

  /**
   * Tests the condition derivatives.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param string $purchasable_entity_type_id
   *   The purchasable entity type ID.
   * @param string $expected_label
   *   The expected plugin label.
   *
   * @dataProvider derivativeData
   */
  public function testDerivative(string $base_plugin_id, string $purchasable_entity_type_id, string $expected_label) {
    $plugin_manager = $this->container->get('plugin.manager.commerce_condition');
    $plugin = $plugin_manager->getDefinition($base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $purchasable_entity_type_id);
    $this->assertNotNull($plugin);
    $this->assertEquals($expected_label, $plugin['label']);
    $this->assertEquals($purchasable_entity_type_id, $plugin['purchasable_entity_type']);
  }

  /**
   * The test data.
   *
   * @return \Generator
   *   The data.
   */
  public function derivativeData(): \Generator {
    yield [
      'order_purchased_entity',
      'commerce_product_variation',
      'Product variation',
    ];
    yield [
      'order_item_purchased_entity',
      'commerce_product_variation',
      'Product variation',
    ];
  }

}
