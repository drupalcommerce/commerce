<?php

/**
 * @file
 * Definition of \Drupal\commerce_product\Tests\CommerceProductTestBase.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for shortcut test cases.
 */
abstract class CommerceProductTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce',
    'commerce_store',
    'commerce_product',
    'field',
    'field_ui',
    'options',
    'entity_reference'
  ];

  /**
   * User with permission to administer products.
   */
  protected $adminUser;

  /**
   * The product to test against
   */
  protected $product;

  /**
   * The store to test against
   */
  protected $commerce_store;

  protected function setUp() {
    parent::setUp();
    // Create a commerce store.
    $name = strtolower($this->randomMachineName(8));

    $store_type = $this->createEntity('commerce_store_type', [
        'id' => 'foo',
        'label' => 'Label of foo',
      ]
    );

    $this->commerce_store = $this->createEntity('commerce_store', [
        'type' => $store_type->id(),
        'name' => $name,
        'mail' => \Drupal::currentUser()->getEmail(),
        'default_currency' => 'EUR',
      ]
    );

    $this->adminUser = $this->drupalCreateUser(
      [
        'administer products',
        'administer product types',
        'administer commerce_product fields',
        'access administration pages',
      ]
    );
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a new entity
   *
   * @param string $entityType
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createEntity($entityType, $values) {
    $entity = entity_create($entityType, $values);
    $status = $entity->save();

    $this->assertEqual(
      $status,
      SAVED_NEW,
      SafeMarkup::format('Created %label entity %type.', [
          '%label' => $entity->getEntityType()->getLabel(),
          '%type' => $entity->id()]
      )
    );

    return $entity;
  }

}
