<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductTestBase.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;

/**
 * Defines base class for shortcut test cases.
 */
abstract class ProductTestBase extends WebTestBase {

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
    'block'
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
   * The stores to test against
   */
  protected $stores;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser([
      'administer products',
      'administer product types',
      'administer commerce_product fields',
      'access administration pages',
      'administer commerce_product_variation fields'
    ]);
    $this->drupalLogin($this->adminUser);

    $storeType = $this->createEntity('commerce_store_type', [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ]);

    $this->stores = [];
    for ($i = 0; $i < 3; $i++) {
      $this->stores[] = $this->createEntity('commerce_store', [
        'type' => $storeType->id(),
        'name' => $this->randomMachineName(8),
        'mail' => \Drupal::currentUser()->getEmail(),
        'default_currency' => 'EUR',
      ]);
    }
  }

  /**
   * Creates a new entity
   *
   * @param string $entityType
   *   The entity type.
   * @param array $values
   *   The values used to create the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createEntity($entityType, $values) {
    $storage = \Drupal::entityManager()->getStorage($entityType);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id()
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

  /**
   * Asserts that the passed field values are correct.
   *
   * Ignores differences in ordering.
   *
   * @param array $fieldValues
   *   The field values.
   * @param array $expectedValues
   *   The expected values.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  protected function assertFieldValues(array $fieldValues, array $expectedValues, $message = '') {
    $valid = TRUE;
    if (count($fieldValues) == count($expectedValues)) {
      foreach ($expectedValues as $value) {
        if (!in_array($value, $fieldValues)) {
          $valid = FALSE;
          break;
        }
      }
    }
    else {
      $valid = FALSE;
    }

    $this->assertTrue($valid, $message);
  }

}
