<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderReassignTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Entity\Order;

/**
 * Tests the commerce_order reassign form.
 *
 * @group commerce
 */
class OrderReassignTest extends OrderTestBase {

  /**
   * Tests the reassign form with a new user.
   */
  public function testOrderReassign() {
    $line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
      'unit_price' => [
        'amount' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser->id(),
      'line_items' => [$line_item],
    ]);

    $this->assertTrue($order->hasLinkTemplate('reassign-form'));

    $this->drupalGet($order->toUrl('reassign-form')->toString());
    $values = [
      'customer_type' => 'new',
    ];
    $this->drupalPostAjaxForm(NULL, $values, 'customer_type');
    $values = [
      'customer_type' => 'new',
      'mail' => 'example@example.com',
    ];
    $this->drupalPostForm(NULL, $values, 'Reassign order');
    $this->assertUrl($order->toUrl('collection')->toString());

    // Reload the order.
    \Drupal::service('entity_type.manager')->getStorage('commerce_order')->resetCache([$order->id()]);
    $order = Order::load($order->id());
    $this->assertEqual($order->getOwner()->getEmail(), 'example@example.com');
    $this->assertEqual($order->getEmail(), 'example@example.com');
  }

}
