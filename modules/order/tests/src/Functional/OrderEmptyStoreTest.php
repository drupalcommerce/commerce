<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Empty Store order page test.
 *
 * @group commerce
 */
class OrderEmptyStoreTest extends CommerceBrowserTestBase {

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
      'administer orders',
      'administer order types',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests order create page.
   */
  public function testCreateOrderPage() {
    $this->drupalGet('admin/commerce/orders');
    $this->clickLink('Create a new order');

    // Check that the link is present.
    $session = $this->assertSession();
    $session->linkExists('Add a new store.');
    $session->linkByHrefExists(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }

}
