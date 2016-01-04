<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderTestBase.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Tests\StoreTestBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for commerce_order test cases.
 */
abstract class OrderTestBase extends WebTestBase {

  /**
   * The variation to test against
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The store to test against
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
    'commerce',
    'commerce_product',
    'commerce_order',
    'commerce_price',
    'inline_entity_form',
    'block'
  ];

  /**
   * A user with permission to administer orders.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser([
      'administer orders',
      'administer order types',
      'administer line item types',
      'access administration pages',
    ]);

    // Create a store
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

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a new entity
   *
   * @param string $entity_type
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createEntity($entity_type, $values) {
    $entity = \Drupal::service('entity_type.manager')
      ->getStorage($entity_type)
      ->create($values);
    $status = $entity->save();

    $this->assertEqual(
      $status,
      SAVED_NEW,
      SafeMarkup::format('Created %label entity %type.', [
          '%label' => $entity->getEntityType()->getLabel(),
          '%type' => $entity->id()
        ]
      )
    );

    return $entity;
  }
}
