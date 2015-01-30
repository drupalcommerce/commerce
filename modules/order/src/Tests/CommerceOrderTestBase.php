<?php

/**
 * @file
 * Definition of \Drupal\commerce_order\Tests\CommerceOrderTestBase.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for shortcut test cases.
 */
abstract class CommerceOrderTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('commerce', 'commerce_order');

  /**
   * User with permission to administer products.
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array(
      'administer orders',
      'administer order types',
      'access administration pages',
    ));
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
      String::format('Created %label entity %type.',
        array(
          '%label' => $entity->getEntityType()->getLabel(),
          '%type' => $entity->id()
        )
      )
    );

    return $entity;
  }
}
