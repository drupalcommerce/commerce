<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests order UI behavior when there are no stores.
 *
 * @group commerce
 */
class OrderNoStoreTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer commerce_order_type',
      'access commerce_order overview',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating an order.
   */
  public function testCreateOrder() {
    $this->store->delete();
    $this->drupalGet('admin/commerce/orders');
    $this->clickLink('Create a new order');

    // Check that the warning is present.
    $session = $this->assertSession();
    $session->pageTextContains("Orders can't be created until a store has been added.");
    $session->linkExists('Add a new store.');
    $session->linkByHrefExists(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }

}
