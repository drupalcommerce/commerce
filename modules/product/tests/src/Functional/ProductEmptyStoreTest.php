<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Empty Store product page test.
 *
 * @group commerce
 */
class ProductEmptyStoreTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
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

    // Check that the link is present.
    $session = $this->assertSession();
    $session->linkExists('Add a new store.');
    $session->linkByHrefExists(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }
}
