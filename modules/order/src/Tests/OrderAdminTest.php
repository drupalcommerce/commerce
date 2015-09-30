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
class OrderAdminTest extends OrderTestBase {

  /**
   * Tests creating a Order programaticaly and through the add form.
   */
  public function testCreateOrder() {
    // Create a order through the add form.
    $this->drupalGet('/admin/commerce/orders');
    $this->clickLink('Create a new order');

    $entity = $this->variation->getSku() . ' (' . $this->variation->id() . ')';
    $values = [
      'line_items[form][inline_entity_form][purchased_entity][0][target_id]' => $entity,
      'line_items[form][inline_entity_form][quantity][0][value]' => 1,
      'line_items[form][inline_entity_form][unit_price][0][amount]' => '9.99'
    ];
    $this->drupalPostForm(NULL, $values, t('Create entity'));

    $values = [
      'store_id' => $this->store->id(),
      'mail[0][value]' => $this->loggedInUser->getEmail()
    ];
    $this->drupalPostForm(NULL, $values, t('Save'));

    $order_number = $this->cssSelect('tr td.views-field-order-number');
    $this->assertEqual(count($order_number), 1, 'Order exists in the table.');
  }

  /**
   * Tests deleting a order.
   */
  public function testDeleteOrder() {
    // Create a new order.
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
    ]);
    $orderExists = (bool) Order::load($order->id());
    $this->assertTrue($orderExists, 'The order has been created in the database.');

    $this->drupalGet('admin/commerce/orders/' . $order->id() . '/delete');
    $this->assertRaw(t('Are you sure you want to delete the order %label?', [
      '%label' => $order->label(),
    ]));
    $this->assertText(t('This action cannot be undone.'), 'The order deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    // Remove the entity from cache and check if the order is deleted.
    \Drupal::entityManager()->getStorage('commerce_order')->resetCache([$order->id()]);
    $orderExists = (bool) Order::load('commerce_order', $order->id());
    $this->assertFalse($orderExists, 'The order has been deleted from the database.');
  }
}
