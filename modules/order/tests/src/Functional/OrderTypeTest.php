<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\OrderType;

/**
 * Tests the commerce_order_type entity forms.
 *
 * @group commerce
 */
class OrderTypeTest extends OrderBrowserTestBase {

  /**
   * Tests if the default Order Type was created.
   */
  public function testDefaultOrderType() {
    $order_types = OrderType::loadMultiple();
    $this->assertTrue(isset($order_types['default']), 'Order Type Order is available');

    $order_type = OrderType::load('default');
    $this->assertEquals($order_types['default'], $order_type, 'The correct Order Type is loaded');
  }

  /**
   * Tests creating a Order Type programaticaly and through the add form.
   */
  public function testCreateOrderType() {
    // Create a order type programmaticaly.
    $type = $this->createEntity('commerce_order_type', [
      'id' => 'kitten',
      'label' => 'Label of kitten',
      'workflow' => 'order_default',
    ]);

    $type_exists = (bool) OrderType::load($type->id());
    $this->assertTrue($type_exists, 'The new order type has been created in the database.');

    // Create a order type through the add form.
    $this->drupalGet('/admin/commerce/config/order-types');
    $this->getSession()->getPage()->clickLink('Add a new order type');

    $values = [
      'id' => 'foo',
      'label' => 'Label of foo',
    ];
    $this->submitForm($values, t('Save'));

    $type_exists = (bool) OrderType::load($values['id']);
    $this->assertTrue($type_exists, 'The new order type has been created in the database.');
  }

  /**
   * Tests deleting a Order Type programmaticaly and through the form.
   */
  public function testDeleteOrderType() {
    // Create a order type programmaticaly.
    $type = $this->createEntity('commerce_order_type', [
      'id' => 'foo',
      'label' => 'Label for foo',
      'workflow' => 'order_default',
    ]);
    commerce_order_add_line_items_field($type);

    // Create a order.
    $order = $this->createEntity('commerce_order', [
      'type' => $type->id(),
      'mail' => $this->loggedInUser->getEmail(),
    ]);

    // Try to delete the order type.
    $this->drupalGet('admin/commerce/config/order-types/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('@type is used by 1 order on your site. You can not remove this order type until you have removed all of the @type orders.', ['@type' => $type->label()]));
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    // Deleting the order type when its not being referenced by a order.
    $order->delete();
    $this->drupalGet('admin/commerce/config/order-types/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the order type @label?', ['@label' => $type->label()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));
    $type_exists = (bool) OrderType::load($type->id());
    $this->assertFalse($type_exists, 'The order type has been deleted from the database.');
  }

}
