<?php
/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderEmptyStoreTestBase,
 * Contains \Drupal\commerce_order\Tests\OrderEmptyStoreTest.
 */
namespace Drupal\commerce_order\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\Core\Url;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Core\Entity\EntityInterface;
/**
 * Defines base class for Empty store test cases.
 */
abstract class OrderEmptyStoreTestBase extends CommerceTestBase {

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
      'administer orders',
      'administer order types',
      'administer line item types',
    ], parent::getAdministratorPermissions());
  }
}


/**
 * Empty Store order page test.
 *
 * @group commerce
 */
class OrderEmptyStoreTest extends OrderEmptyStoreTestBase {
  /**
   * Tests creating a product.
   */
  function testCreateOrder() {
    $this->drupalGet('admin/commerce/orders');
    $this->clickLink('Create a new order');

    // Check the link is present.
    $this->assertLink(t('Add a new store.'));
    $this->assertLinkByHref(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }
}
