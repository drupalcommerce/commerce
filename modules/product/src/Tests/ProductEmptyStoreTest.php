<?php
/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductEmptyStoreTestBase,
 * Contains \Drupal\commerce_product\Tests\ProductEmptyStoreTest.
 */
namespace Drupal\commerce_product\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\Core\Url;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Core\Entity\EntityInterface;
/**
 * Defines base class for Empty store test cases.
 */
abstract class ProductEmptyStoreTestBase extends CommerceTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'field_ui',
    'options',
    'taxonomy',
  ];

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $product;

  /**
   * The stores to test against.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface[]
   */
  protected $stores;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer products',
      'administer product types',
      'administer commerce_product fields',
      'administer commerce_product_variation fields',
    ], parent::getAdministratorPermissions());
  }
}


/**
 * Empty Store product page test.
 *
 * @group commerce
 */
class ProductEmptyStoreTest extends ProductEmptyStoreTestBase {
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
