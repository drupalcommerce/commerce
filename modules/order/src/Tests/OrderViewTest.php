<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderViewTest.
 */

namespace Drupal\commerce_order\Tests;

/**
 * Tests viewing commerce_order entities.
 *
 * @group commerce
 */
class OrderViewTest extends OrderTestBase {

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
    $this->drupalGet('admin/commerce/orders/' . $order->id());
    $this->assertResponse(200);
    $this->assertNoText("You are not authorized to access this page.");

    // Order displays email address.
    $this->assertText($this->loggedInUser->getEmail());

    // Logout and check that anonymous users cannot see the order admin screen
    // and receive a 403 error code.
    $this->drupalLogout();

    $this->drupalGet('admin/commerce/orders/' . $order->id());
    $this->assertResponse(403);
    $this->assertText("You are not authorized to access this page.");
  }

}
