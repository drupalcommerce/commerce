<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\profile\Entity\Profile;

/**
 * Tests the commerce_order entity forms.
 *
 * @group commerce
 */
class OrderAdminTest extends OrderBrowserTestBase {

  use AssertMailTrait;

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
    $this->container->get('module_installer')->install(['profile']);

    $profile_values = [
      'type' => 'customer',
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
    // Create an order through the add form.
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
    $this->assertSession()->fieldExists('billing_profile[0][profile][address][0][address][given_name]');
    $this->assertSession()->fieldExists('order_items[form][inline_entity_form][purchased_entity][0][target_id]');
    $this->assertSession()->fieldExists('order_items[form][inline_entity_form][quantity][0][value]');
    $this->assertSession()->fieldExists('order_items[form][inline_entity_form][unit_price][0][amount][number]');
    $this->assertSession()->buttonExists('Create order item');
    $entity = $this->variation->getSku() . ' (' . $this->variation->id() . ')';

    // Test that commerce_order_test_field_widget_form_alter() has the expected
    // outcome.
    $this->assertSame([], \Drupal::state()->get("commerce_order_test_field_widget_form_alter"));

    $checkbox = $this->getSession()->getPage()->findField('Override the unit price');
    if ($checkbox) {
      $checkbox->check();
    }
    $edit = [
      'order_items[form][inline_entity_form][purchased_entity][0][target_id]' => $entity,
      'order_items[form][inline_entity_form][quantity][0][value]' => '1',
      'order_items[form][inline_entity_form][unit_price][0][amount][number]' => '9.99',
    ];
    $this->submitForm($edit, 'Create order item');
    $this->submitForm([], t('Edit'));
    $this->assertSession()->fieldExists('order_items[form][inline_entity_form][entities][0][form][purchased_entity][0][target_id]');
    $this->assertSession()->fieldExists('order_items[form][inline_entity_form][entities][0][form][quantity][0][value]');
    $this->assertSession()->fieldExists('order_items[form][inline_entity_form][entities][0][form][unit_price][0][amount][number]');
    $this->assertSession()->buttonExists('Update order item');

    $checkbox = $this->getSession()->getPage()->findField('Override the unit price');
    if ($checkbox) {
      $checkbox->check();
    }
    $edit = [
      'order_items[form][inline_entity_form][entities][0][form][quantity][0][value]' => 3,
      'order_items[form][inline_entity_form][entities][0][form][unit_price][0][amount][number]' => '1.11',
    ];
    $this->submitForm($edit, 'Update order item');
    $edit = [
      'billing_profile[0][profile][address][0][address][given_name]' => 'John',
      'billing_profile[0][profile][address][0][address][family_name]' => 'Smith',
      'billing_profile[0][profile][address][0][address][address_line1]' => '123 street',
      'billing_profile[0][profile][address][0][address][postal_code]' => '94043',
      'billing_profile[0][profile][address][0][address][locality]' => 'Mountain View',
      'billing_profile[0][profile][address][0][address][administrative_area]' => 'CA',
    ];
    // There is no adjustment - the order should save successfully.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The order has been successfully saved.');

    // Use an adjustment that is not locked by default.
    $this->clickLink('Edit');
    $edit = [
      'adjustments[0][type]' => 'fee',
      'adjustments[0][definition][label]' => '',
      'adjustments[0][definition][amount][number]' => '2.00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The adjustment label field is required.');
    $edit['adjustments[0][definition][label]'] = 'Test fee';
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The order has been successfully saved.');

    $this->drupalGet('/admin/commerce/orders');
    $order_number = $this->getSession()->getPage()->findAll('css', 'tr td.views-field-order-number');
    $this->assertEquals(1, count($order_number), 'Order exists in the table.');

    $order = Order::load(1);
    $this->assertEquals(1, count($order->getItems()));
    $this->assertEquals(new Price('5.33', 'USD'), $order->getTotalPrice());
    $this->assertCount(1, $order->getAdjustments());
  }

  /**
   * Tests editing an order.
   */
  public function testEditOrder() {
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
    ]);
    $order->save();

    $adjustments = [];
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
    ]);
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => 'Handling fee',
      'amount' => new Price('10.00', 'USD'),
    ]);
    $order->addAdjustment($adjustments[0]);
    $order->addAdjustment($adjustments[1]);
    $order->save();

    $this->drupalGet($order->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('adjustments[0][definition][label]', '10% off');
    $this->assertSession()->fieldValueEquals('adjustments[1][definition][label]', 'Handling fee');
    $this->assertSession()->optionExists('adjustments[2][type]', 'Custom');
    $this->assertSession()->optionNotExists('adjustments[2][type]', 'Test order adjustment type');
  }

  /**
   * Tests editing an order after the customer was deleted.
   */
  public function testEditOrderWithDeletedCustomer() {
    $customer = $this->drupalCreateUser();
    // The profile is not associated with the customer to avoid #2995300.
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $profile->save();
    $order_item = OrderItem::create([
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'uid' => $customer->id(),
      'store_id' => $this->store,
      'billing_profile' => $profile,
      'order_items' => [$order_item],
    ]);
    $order->save();
    $customer->delete();

    $this->drupalGet($order->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('The order has been successfully saved.');
  }

  /**
   * Tests deleting an order.
   */
  public function testDeleteOrder() {
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
    ]);
    $this->drupalGet($order->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the order @label?', [
      '@label' => $order->label(),
    ]));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    $this->container->get('entity_type.manager')->getStorage('commerce_order')->resetCache([$order->id()]);
    $order_exists = (bool) Order::load($order->id());
    $this->assertEmpty($order_exists, 'The order has been deleted from the database.');
  }

  /**
   * Tests unlocking an order.
   */
  public function testUnlockOrder() {
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'locked' => TRUE,
    ]);
    $this->drupalGet($order->toUrl('unlock-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Are you sure you want to unlock the order @label?', [
      '@label' => $order->label(),
    ]));
    $this->submitForm([], t('Unlock'));

    $this->container->get('entity_type.manager')->getStorage('commerce_order')->resetCache([$order->id()]);
    $order = Order::load($order->id());
    $this->assertFalse($order->isLocked());
  }

  /**
   * Tests resending the order receipt.
   */
  public function testResendReceipt() {
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'locked' => TRUE,
    ]);

    // No access until the order has been placed.
    $this->drupalGet($order->toUrl('resend-receipt-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Placing the order sends the receipt.
    $transition = $order->getState()->getTransitions();
    $order->getState()->applyTransition($transition['place']);
    $order->save();
    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = array_pop($emails);
    $this->assertEquals('Order #' . $order->getOrderNumber() . ' confirmed', $email['subject']);

    // Change the order number to differentiate from automatic email.
    $order->setOrderNumber('2018/01');
    $order->save();

    $this->drupalGet($order->toUrl('resend-receipt-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to resend the receipt for order @label?', [
      '@label' => $order->label(),
    ]));
    $this->submitForm([], t('Resend receipt'));

    $emails = $this->getMails();
    $this->assertEquals(2, count($emails));
    $email = array_pop($emails);
    $this->assertEquals('Order #2018/01 confirmed', $email['subject']);
    $this->assertSession()->pageTextContains("Order receipt resent.");
  }

  /**
   * Tests that an admin can view an order's details.
   */
  public function testAdminOrderView() {
    // Start from an order without any order items.
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'store_id' => $this->store->id(),
      'mail' => $this->loggedInUser->getEmail(),
      'state' => 'draft',
      'uid' => $this->loggedInUser,
    ]);

    // First test that the current admin user can see the order.
    $this->drupalGet($order->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->loggedInUser->getEmail());

    // Confirm that the order item table is showing the empty text.
    $this->assertSession()->pageTextContains('There are no order items yet.');
    $this->assertSession()->pageTextNotContains('Subtotal');

    // Confirm that the transition buttons are visible and functional.
    $workflow = $order->getState()->getWorkflow();
    $transitions = $workflow->getAllowedTransitions($order->getState()->getId(), $order);
    foreach ($transitions as $transition) {
      $this->assertSession()->buttonExists($transition->getLabel());
    }
    $this->click('input.js-form-submit#edit-place');
    $this->assertSession()->buttonNotExists('Place order');
    $this->assertSession()->buttonNotExists('Cancel order');

    // Add an order item, confirm that it is displayed.
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order->setItems([$order_item]);
    $order->save();

    $this->drupalGet($order->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('There are no order items yet.');
    $this->assertSession()->pageTextContains('$999.00');
    $this->assertSession()->pageTextContains('Subtotal');

    // Logout and check that anonymous users cannot see the order admin screen
    // and receive a 403 error code.
    $this->drupalLogout();

    $this->drupalGet($order->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
  }

}
