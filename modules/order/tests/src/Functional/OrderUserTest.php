<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

/**
 * Tests normal user operations with orders.
 *
 * @group commerce
 */
class OrderUserTest extends OrderBrowserTestBase {

  /**
   * A test user with normal privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $permissions = [
      'view own commerce_order',
    ];

    $this->user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests viewing a created order.
   */
  public function testViewOrder() {
    $uid = $this->loggedInUser->id();

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'uid' => $uid,
      'order_items' => [$order_item],
      'mail' => 'testViewOrder@example.com',
      'order_number' => '1',
    ]);
    $order->save();

    // Check that we can view the orders page.
    $this->drupalGet('/user/' . $uid . '/orders/');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the order is available.
    $this->assertSession()->linkByHrefExists('/user/' . $uid . '/orders/' . $order->id());

    // Click order and make sure it works.
    $this->getSession()->getPage()->clickLink($order->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($order->getEmail());
  }

}
