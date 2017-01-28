<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests product UI behavior when there are no stores.
 *
 * @group commerce
 */
class ProductNoStoreTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
      'administer commerce_product_type',
      'access commerce_product overview',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a product.
   */
  public function testCreateProduct() {
    $this->store->delete();
    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add product');

    // Check that the warning is present.
    $session = $this->assertSession();
    $session->pageTextContains("Products can't be created until a store has been added.");
    $session->linkExists('Add a new store.');
    $session->linkByHrefExists(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }

}
