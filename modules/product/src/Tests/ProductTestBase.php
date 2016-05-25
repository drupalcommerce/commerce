<?php

namespace Drupal\commerce_product\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\commerce_store\StoreCreationTrait;

/**
 * Defines base class for shortcut test cases.
 */
abstract class ProductTestBase extends CommerceTestBase {

  use EntityReferenceTestTrait;
  use StoreCreationTrait;

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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->stores = [];
    for ($i = 0; $i < 3; $i++) {
      $this->stores[] = $this->createStore();
    }
  }

}
