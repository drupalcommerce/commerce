<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Entity\Order;
use Drupal\profile\Entity\Profile;

/**
 * Tests the commerce_order entity forms.
 *
 * @group commerce
 */
class OrderAdminTest extends OrderTestBase {

  /**
   * The profile to test against
   *
   * @var \Drupal\profile\Entity\Profile
   */
  protected $billingProfile;

  protected function setUp() {
    parent::setUp();
    \Drupal::service('module_installer')->install(['profile']);

    // Create a billing profile.
    $profile_values = [
      'type' => 'billing',
      'uid' => 1,
      'status' => 1,
    ];
    $this->billingProfile = Profile::create($profile_values);
    $this->billingProfile->save();
  }

  /**
   * Tests creating/editing an Order.
   * The edit form is part of creating a new order.
   */
  public function testCreateOrder() {
    // Create a order through the create form.
    $this->drupalGet('/admin/commerce/orders');
    $this->clickLink('Create a new order');

    $user = $this->loggedInUser->getAccountName() . ' (' . $this->loggedInUser->id() . ')';
    $edit = [
      'customer_type' => 'existing',
      'uid' => $user,
    ];
    $this->drupalPostForm(NULL, $edit, t('Create'));

    // Test the edit form page returned.
    $this->assertResponse(200, 'The order edit form can be accessed.');

    // Check the integrity of the edit form.
    $this->assertFieldByName('billing_profile', NULL, 'Billing profile field is present');
    $this->assertFieldByName('line_items[form][inline_entity_form][purchased_entity][0][target_id]', NULL, 'Purchased entity field is present');
    $this->assertFieldByName('line_items[form][inline_entity_form][quantity][0][value]', NULL, 'Quantity field is present');
    $this->assertFieldByName('line_items[form][inline_entity_form][unit_price][0][amount]', NULL, 'Unit price field is present');
    $this->assertFieldsByValue(t('Create line item'), NULL, 'Create line item button is present');

    // Submit line item values.
    $entity = $this->variation->getSku() . ' (' . $this->variation->id() . ')';
    $edit = [
      'line_items[form][inline_entity_form][purchased_entity][0][target_id]' => $entity,
      'line_items[form][inline_entity_form][quantity][0][value]' => 1,
      'line_items[form][inline_entity_form][unit_price][0][amount]' => '9.99',
    ];
    $this->drupalPostForm(NULL, $edit, t('Create line item'));

    // Check the line item edit form.
    $this->drupalPostForm(NULL, [], t('Edit'));
    $this->assertFieldByName('line_items[form][inline_entity_form][entities][0][form][purchased_entity][0][target_id]', NULL, 'SKU field is present');
    $this->assertFieldByName('line_items[form][inline_entity_form][entities][0][form][quantity][0][value]', NULL, 'Price field is present');
    $this->assertFieldByName('line_items[form][inline_entity_form][entities][0][form][unit_price][0][amount]', NULL, 'Status field is present');
    $this->assertFieldsByValue(t('Update line item'), NULL, 'Update line item button is present');

    // Update/edit line item.
    $edit = [
      'line_items[form][inline_entity_form][entities][0][form][quantity][0][value]' => 3,
      'line_items[form][inline_entity_form][entities][0][form][unit_price][0][amount]' => '1.11',
    ];
    $this->drupalPostForm(NULL, $edit, t('Update line item'));

    // Submit order values.
    $edit = [
      'billing_profile' => $this->billingProfile->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Test results.
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
    $order_exists = (bool) Order::load($order->id());
    $this->assertTrue($order_exists, 'The order has been created in the database.');

    // Delete the order created.
    $this->drupalGet($order->toUrl('delete-form'));
    $this->assertResponse(200, 'The order delete form can be accessed.');
    $this->assertRaw(t('Are you sure you want to delete the order %label?', [
      '%label' => $order->label(),
    ]));
    $this->assertText(t('This action cannot be undone.'), 'The order deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    // Remove the entity from cache and check if the order is deleted.
    \Drupal::service('entity_type.manager')->getStorage('commerce_order')->resetCache([$order->id()]);
    $order_exists = (bool) Order::load('commerce_order', $order->id());
    $this->assertFalse($order_exists, 'The order has been deleted from the database.');
  }

}
