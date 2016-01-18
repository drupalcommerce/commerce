<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductTestBase.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;

/**
 * Defines base class for shortcut test cases.
 */
abstract class ProductTestBase extends CommerceTestBase {

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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $store_type = $this->createEntity('commerce_store_type', [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ]);

    $this->stores = [];
    for ($i = 0; $i < 3; $i++) {
      $this->stores[] = $this->createEntity('commerce_store', [
        'type' => $store_type->id(),
        'name' => $this->randomMachineName(8),
        'mail' => \Drupal::currentUser()->getEmail(),
        'default_currency' => 'EUR',
      ]);
    }
  }

}
