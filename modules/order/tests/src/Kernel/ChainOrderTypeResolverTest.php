<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\OrderItem;

/**
 * Tests the chain order type resolver.
 *
 * @group commerce
 */
class ChainOrderTypeResolverTest extends OrderKernelTestBase {

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
