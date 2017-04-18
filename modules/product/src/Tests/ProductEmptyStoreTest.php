<?php

namespace Drupal\commerce_product\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\Core\Url;

/**
 * Empty Store product page test.
 *
 * @group commerce
 */
class ProductEmptyStoreTest extends CommerceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_store',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer products',
      'administer product types',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a product.
   */
  function testCreateProduct() {
    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add product');

    // Check the link is present.
    $this->assertLink(t('Add a new store.'));
    $this->assertLinkByHref(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }
}
