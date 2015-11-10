<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Tests\StoreTestBase.
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
  public static $modules = ['commerce', 'commerce_store', 'block'];

  /**
   * User with permission to administer the commerce store.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $permissions = [
      'view the administration theme',
      'administer store types',
      'administer stores',
      'configure store',
    ];
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
    $entity = \Drupal::service('entity_type.manager')->getStorage($entityType)->create($values);
    $status = $entity->save();
    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created %label entity %type.', ['%label' => $entity->getEntityType()->getLabel(), '%type' => $entity->id()]));

    return $entity;
  }

}
