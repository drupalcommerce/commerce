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

    // Create a order through the add form.
    $this->drupalGet('/admin/commerce/orders');
    $this->clickLink('Create a new order');

    $values = array(
      'mail[0][value]' => $this->loggedInUser->getEmail(),
    );
    $this->drupalPostForm(NULL, $values, t('Save'));
  }

  /**
   * Tests deleting a order.
   */
  public function testDeleteOrder() {
    // Create a new order.
    $order = $this->createEntity('commerce_order', array(
        'type' => 'order',
        'mail' => $this->loggedInUser->getEmail(),
      )
    );
    $orderExists = (bool) Order::load($order->id());
    $this->assertTrue($orderExists, 'The order has been created in the database.');

    $this->drupalGet('admin/commerce/orders/' . $order->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the order %label?', array(
        '%label' => $order->label(),
      ))
    );
    $this->assertText(t('This action cannot be undone.'), 'The order deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    // Remove the entity from cache and check if the order is deleted.
    \Drupal::entityManager()->getStorage('commerce_order')->resetCache(array($order->id()));
    $orderExists = (bool) Order::load('commerce_order', $order->id());
    $this->assertFalse($orderExists, 'The order has been deleted from the database.');
  }
}
