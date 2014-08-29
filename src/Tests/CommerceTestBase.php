<?php

/**
 * @file
 * Definition of Drupal\commerce\Tests\CommerceTestBase.
 */

namespace Drupal\commerce\Tests;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for commerce test cases.
 */
abstract class CommerceTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('devel', 'commerce');

  /**
   * User with permission to administer the commerce store.
   */
  protected $admin_user;

  protected function setUp() {
    parent::setUp();

    $permissions = array(
      'view the administration theme',
      'administer store types',
      'administer stores',
      'configure store'
    );

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Creates a new entity
   *
   * @param $entityType
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  function createEntity($entityType, $values) {
    $entity = entity_create($entityType, $values);
    $status = $entity->save();

    $this->assertEqual(
      $status,
      SAVED_NEW,
      String::format('Created %label entity %type.',
        array('%label' => $entity->getEntityType()->getLabel(), '%type' => $entity->id())
      )
    );

    return $entity;
  }

}
