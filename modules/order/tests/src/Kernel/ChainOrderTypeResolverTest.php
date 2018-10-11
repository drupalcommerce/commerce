<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the chain order type resolver.
 *
 * @group commerce
 */
class ChainOrderTypeResolverTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * Tests resolving the order type.
   */
  public function testOrderTypeResolution() {
    $order_item = OrderItem::create([
      'type' => 'test',
    ]);
    $order_item->save();

    $resolver = $this->container->get('commerce_order.chain_order_type_resolver');

    $order_type = $resolver->resolve($order_item);

    $this->assertEquals('default', $order_type);
  }

}
