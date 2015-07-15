<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Entity\Order;

/**
 * Tests the commerce_order entity forms.
 *
 * @group commerce
 */
class OrderTest extends CommerceOrderTestBase {

  /**
   * Tests creating a Order programaticaly and through the add form.
   */
  public function testCreateOrder() {
    // Create a order programmaticaly.
    $order = $this->createEntity('commerce_order', array(
        'type' => 'order',
        'mail' => $this->loggedInUser->getEmail(),
      )
    );

    $orderExists = (bool) Order::load($order->id());
    $this->assertTrue($orderExists, 'The new order has been created in the database.');
    $this->assertEqual($order->id(), $order->getOrderNumber(), 'The order number matches the order ID');
  }
}
