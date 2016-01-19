<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderTestBase.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce\Tests\CommerceTestBase;

/**
 * Defines base class for commerce_order test cases.
 */
abstract class OrderTestBase extends CommerceTestBase {

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The store to test against.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_price',
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a store.
    $values = [
      'name' => t('Default store'),
      'uid' => 1,
      'mail' => \Drupal::config('system.site')->get('mail'),
      'type' => 'default',
      'default_currency' => 'USD',
      'address' => [
        'country_code' => 'GB',
        'locality' => 'London',
        'postal_code' => 'NW1 6XE',
        'address_line1' => '221B Baker Street',
      ],
    ];
    $this->store = Store::create($values);
    $this->store->save();

    // Set as default store.
    \Drupal::configFactory()->getEditable('commerce_store.settings')
      ->set('default_store', $this->store->uuid())->save();

    // Create a product variation.
    $values = [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'EUR',
      ],
    ];
    $this->variation = ProductVariation::create($values);
    $this->variation->save();

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'variations' => [$this->variation],
    ]);
  }

}
