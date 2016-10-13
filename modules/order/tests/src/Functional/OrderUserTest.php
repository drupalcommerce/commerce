<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;

/**
 * Tests normal user operations with orders.
 *
 * @group commerce
 */
class OrderUserTest extends OrderBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public function getAdministratorPermissions() {
    return array_merge([
      'view own commerce_order',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests viewing a created order.
   */
  public function testViewOrder() {
    $uid = $this->loggedInUser->id();

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'uid' => $uid,
      'mail' => 'testViewOrder@example.com',
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
