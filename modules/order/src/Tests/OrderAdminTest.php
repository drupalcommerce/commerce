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

    $profile_values = [
      'type' => 'billing',
      'uid' => 1,
      'status' => 1,
    ];
    $this->billingProfile = Profile::create($profile_values);
    $this->billingProfile->save();
  }

  /**
   * Tests creating a Order programaticaly and through the add form.
   */
  public function testCreateOrder() {
    // Create a order through the add form.
    $this->drupalGet('/admin/commerce/orders');
    $this->clickLink('Create a new order');

    $user = $this->loggedInUser->getAccountName() . ' (' . $this->loggedInUser->id() . ')';
    $values = [
      'customer_type' => 'existing',
      'uid' => $user,
    ];
    $this->drupalPostForm(NULL, $values, 'Create');

    $entity = $this->variation->getSku() . ' (' . $this->variation->id() . ')';
    $values = [
      'line_items[form][inline_entity_form][purchased_entity][0][target_id]' => $entity,
      'line_items[form][inline_entity_form][quantity][0][value]' => 1,
      'line_items[form][inline_entity_form][unit_price][0][amount]' => '9.99',
    ];
    $this->drupalPostForm(NULL, $values, 'Create line item');

    $values = [
      'billing_profile' => $this->billingProfile->id(),
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
    $order_exists = (bool) Order::load($order->id());
    $this->assertTrue($order_exists, 'The order has been created in the database.');

    $this->drupalGet('admin/commerce/orders/' . $order->id() . '/delete');
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
