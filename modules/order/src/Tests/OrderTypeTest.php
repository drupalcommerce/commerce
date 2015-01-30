<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderTypeTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Entity\OrderType;

/**
 * Tests the commerce_order_type entity forms.
 *
 * @group commerce
 */
class OrderTypeTest extends CommerceOrderTestBase {

  /**
   * Tests if the default Order Type was created.
   */
  public function testDefaultOrderType() {
    $orderTypes = OrderType::loadMultiple();
    $this->assertTrue(isset($orderTypes['order']), 'Order Type Order is available');

    $orderType = OrderType::load('order');
    $this->assertEqual($orderTypes['order'], $orderType, 'The correct Order Type is loaded');
  }

  /**
   * Tests creating a Order Type programaticaly and through the add form.
   */
  public function testCreateOrderType() {
    // Create a order type programmaticaly.
    $type = $this->createEntity('commerce_order_type', array(
        'id' => 'kitten',
        'label' => 'Label of kitten',
      )
    );

    $typeExists = (bool) OrderType::load($type->id());
    $this->assertTrue($typeExists, 'The new order type has been created in the database.');

    // Create a order type through the add form.
    $this->drupalGet('/admin/commerce/config/order-types');
    $this->clickLink('Add a new order type');

    $values = array(
      'id' => 'foo',
      'label' => 'Label of foo',
    );
    $this->drupalPostForm(NULL, $values, t('Save'));

    $typeExists = (bool) OrderType::load($values['id']);
    $this->assertTrue($typeExists, 'The new order type has been created in the database.');
  }

  /**
   * Tests deleting a Order Type programmaticaly and through the form.
   */
  public function testDeleteOrderType() {
    // Create a order type programmaticaly.
    $type = $this->createEntity('commerce_order_type', array(
        'id' => 'foo',
        'label' => 'Label for foo',
      )
    );

    // Create a order.
    $order = $this->createEntity('commerce_order', array(
        'type' => $type->id(),
        'mail' => $this->loggedInUser->getEmail(),
      )
    );

    // Try to delete the order type.
    $this->drupalGet('admin/commerce/config/order-types/' . $type->id() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 order on your site. You can not remove this order type until you have removed all of the %type orders.', array('%type' => $type->label())),
      'The order type will not be deleted until all orders of that type are deleted'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The order type deletion confirmation form is not available');

    // Deleting the order type when its not being referenced by a order.
    $order->delete();
    $this->drupalGet('admin/commerce/config/order-types/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the order type %label?', array(
        '%label' => $type->label(),
      ))
    );
    $this->assertText(t('This action cannot be undone.'), 'The order type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $typeExists = (bool) OrderType::load($type->id());
    $this->assertFalse($typeExists, 'The order type has been deleted from the database.');
  }
}
