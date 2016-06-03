<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\OrderBrowserTestBase;

/**
 * Tests the commerce_order entity forms.
 *
 * @group commerce
 */
class OrderAdminTest extends OrderBrowserTestBase {

  /**
   * The profile to test against.
   *
   * @var \Drupal\profile\Entity\Profile
   */
  protected $billingProfile;

  /**
   * {@inheritdoc}
   */
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
   * Tests creating/editing an Order.
   */
  public function testCreateOrder() {
    // Create a order through the add form.
    $this->drupalGet('/admin/commerce/orders');
    $this->getSession()->getPage()->clickLink('Create a new order');
    $user = $this->loggedInUser->getAccountName() . ' (' . $this->loggedInUser->id() . ')';
    $edit = [
      'customer_type' => 'existing',
      'uid' => $user,
    ];
    $this->submitForm($edit, t('Create'));

    // Check the integrity of the edit form.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('billing_profile');
    $this->assertSession()->fieldExists('line_items[form][inline_entity_form][purchased_entity][0][target_id]');
    $this->assertSession()->fieldExists('line_items[form][inline_entity_form][quantity][0][value]');
    $this->assertSession()->fieldExists('line_items[form][inline_entity_form][unit_price][0][amount]');
    $this->assertSession()->buttonExists('Create line item');
    $entity = $this->variation->getSku() . ' (' . $this->variation->id() . ')';
    $edit = [
      'line_items[form][inline_entity_form][purchased_entity][0][target_id]' => $entity,
      'line_items[form][inline_entity_form][quantity][0][value]' => 1,
      'line_items[form][inline_entity_form][unit_price][0][amount]' => '9.99',
    ];
    $this->submitForm($edit, 'Create line item');
    $this->submitForm([], t('Edit'));
    $this->assertSession()->fieldExists('line_items[form][inline_entity_form][entities][0][form][purchased_entity][0][target_id]');
    $this->assertSession()->fieldExists('line_items[form][inline_entity_form][entities][0][form][quantity][0][value]');
    $this->assertSession()->fieldExists('line_items[form][inline_entity_form][entities][0][form][unit_price][0][amount]');
    $this->assertSession()->buttonExists('Update line item');

    $edit = [
      'line_items[form][inline_entity_form][entities][0][form][quantity][0][value]' => 3,
      'line_items[form][inline_entity_form][entities][0][form][unit_price][0][amount]' => '1.11',
    ];
    $this->submitForm($edit, 'Update line item');
    $edit = [
      'billing_profile' => $this->billingProfile->id(),
    ];
    $this->submitForm($edit, 'Save');

    $order_number = $this->getSession()->getPage()->find('css', 'tr td.views-field-order-number');
    $this->assertEquals(1, count($order_number), 'Order exists in the table.');
  }

  /**
   * Tests deleting a order.
   */
  public function testDeleteOrder() {
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
    ]);
    $this->drupalGet($order->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the order @label?', [
      '@label' => $order->label(),
    ]));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_order')->resetCache([$order->id()]);
    $order_exists = (bool) Order::load($order->id());
    $this->assertFalse($order_exists, 'The order has been deleted from the database.');
  }

}
