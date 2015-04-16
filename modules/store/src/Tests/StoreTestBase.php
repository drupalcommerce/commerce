<?php

/**
 * @file
 * Definition of Drupal\commerce\Tests\StoreTestBase.
 */

namespace Drupal\commerce_store\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for commerce test cases.
 */
abstract class StoreTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('commerce', 'commerce_store');

  /**
   * User with permission to administer the commerce store.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = array(
      'view the administration theme',
      'administer store types',
      'administer stores',
      'configure store',
    );

    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a new entity.
   *
   * @param string $entityType
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entityType, array $values) {
    $entity = entity_create($entityType, $values);
    $status = $entity->save();

    $this->assertEqual(
      $status,
      SAVED_NEW,
      SafeMarkup::format('Created %label entity %type.',
        array('%label' => $entity->getEntityType()->getLabel(), '%type' => $entity->id())
      )
    );

    return $entity;
  }

}
