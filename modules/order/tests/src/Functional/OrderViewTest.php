<?php

namespace Drupal\Tests\commerce_order\Functional;
use Drupal\Tests\commerce_order\OrderBrowserTestBase;

/**
 * Tests viewing commerce_order entities.
 *
 * @group commerce
 */
class OrderViewTest extends OrderBrowserTestBase {

  /**
   * Tests that an admin can view an order's details.
   */
  public function testAdminOrderView() {
    $line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
      'unit_price' => [
        'amount' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$line_item],
    ]);

    // First test that the current admin user can see the order.
    $this->drupalGet($order->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);

    // Order displays email address.
    $this->assertSession()->pageTextContains($this->loggedInUser->getEmail());

    // Logout and check that anonymous users cannot see the order admin screen
    // and receive a 403 error code.
    $this->drupalLogout();

    $this->drupalGet($order->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
  }

}
