<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the checkout of an order.
 *
 * @group commerce
 */
class CheckoutFlowAdminTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['commerce_checkout'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer checkout flows',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests the checkout-workflow admin pages.
   */
  public function testCheckoutFlowAdminPages() {
    // Goto the administration page for Checkout Workflows.
    $this->drupalGet('/admin/commerce/config/checkout-flows/manage/default');
    $this->assertSession()->statusCodeEquals(200);
  }

}
