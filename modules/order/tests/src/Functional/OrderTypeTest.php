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
    $this->assertNotEmpty(isset($order_types['default']), 'Order Type Order is available');

    $order_type = OrderType::load('default');
    $this->assertEquals($order_types['default'], $order_type, 'The correct Order Type is loaded');
  }

  /**
   * Tests creating an order Type programaticaly and through the add form.
   */
  public function testCreateOrderType() {
    // Remove the default order type to be able to test creating the
    // order_items field anew.
    OrderType::load('default')->delete();

    // Create an order type programmaticaly.
    $type = $this->createEntity('commerce_order_type', [
      'id' => 'kitten',
      'label' => 'Label of kitten',
      'workflow' => 'order_default',
    ]);

    $type_exists = (bool) OrderType::load($type->id());
    $this->assertNotEmpty($type_exists, 'The new order type has been created in the database.');

    // Create an order type through the add form.
    $this->drupalGet('/admin/commerce/config/order-types');
    $this->getSession()->getPage()->clickLink('Add order type');

    $values = [
      'id' => 'foo',
      'label' => 'Label of foo',
    ];
    $this->submitForm($values, t('Save'));

    $type_exists = (bool) OrderType::load($values['id']);
    $this->assertNotEmpty($type_exists, 'The new order type has been created in the database.');

    // Testing the target type of the order_items field.
    $settings = $this->config('field.storage.commerce_order.order_items')->get('settings');
    $this->assertEquals('commerce_order_item', $settings['target_type'], t('Order item field target type is correct.'));
  }

  /**
   * Tests draft order refresh options in order type form.
   */
  public function testDraftOrderRefreshSettings() {
    $url = 'admin/commerce/config/order-types/default/edit';
    $this->drupalGet($url);
    $this->assertSession()->fieldExists('refresh_mode');
    $this->assertSession()->fieldExists('refresh_frequency');

    $edit['refresh_mode'] = 'always';
    $edit['refresh_frequency'] = 60;
    $this->submitForm($edit, t('Save'));
    $order_type = OrderType::load('default');
    $this->drupalGet($url);
    $this->assertEquals($order_type->getRefreshMode(), $edit['refresh_mode'], 'The value of the draft order refresh mode has been changed.');
    $this->assertEquals($order_type->getRefreshFrequency(), $edit['refresh_frequency'], 'The value of the draft order refresh frequency has been changed.');
  }

  /**
   * Tests deleting an order Type through the form.
   */
  public function testDeleteOrderType() {
    // Create an order type programmaticaly.
    $type = $this->createEntity('commerce_order_type', [
      'id' => 'foo',
      'label' => 'Label for foo',
      'workflow' => 'order_default',
    ]);
    commerce_order_add_order_items_field($type);

    // Create an order.
    $order = $this->createEntity('commerce_order', [
      'type' => $type->id(),
      'mail' => $this->loggedInUser->getEmail(),
      'store_id' => $this->store,
    ]);

    // Try to delete the order type.
    $this->drupalGet('admin/commerce/config/order-types/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('@type is used by 1 order on your site. You cannot remove this order type until you have removed all of the @type orders.', ['@type' => $type->label()]));
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    // Deleting the order type when its not being referenced by an order.
    $order->delete();
    $this->drupalGet('admin/commerce/config/order-types/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the order type @label?', ['@label' => $type->label()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));
    $type_exists = (bool) OrderType::load($type->id());
    $this->assertEmpty($type_exists, 'The order type has been deleted from the database.');
  }

}
