<?php

namespace Drupal\commerce_order\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\Core\Url;

/**
 * Empty Store order page test.
 *
 * @group commerce
 */
class OrderEmptyStoreTest extends CommerceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'inline_entity_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer orders',
      'administer order types',
      'administer line item types',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests order create page.
   */
  function testCreateOrderPage() {
    $this->drupalGet('admin/commerce/orders');
    $this->clickLink('Create a new order');

    // Check the link is present.
    $this->assertLink(t('Add a new store.'));
    $this->assertLinkByHref(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }
}
