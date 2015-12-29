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
    'commerce',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'field',
    'field_ui',
    'options',
    'taxonomy',
    'block',
  ];

  /**
   * The product to test against
   */
  protected $product;

  /**
   * The stores to test against
   */
  protected $stores;

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

  /**
   * {@inheritdoc}
   */
  protected function defaultAdminUserPermissions() {
    return [
      'view the administration theme',
      'configure store',
      'administer products',
      'administer product types',
      'administer commerce_product fields',
      'access administration pages',
      'administer commerce_product_variation fields',
    ];
  }

}
