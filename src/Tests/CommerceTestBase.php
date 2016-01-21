<?php

/**
 * @file
 * Contains \Drupal\commerce\Tests\CommerceTestBase.
 */

namespace Drupal\commerce\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Test case for Commerce tests.
 */
abstract class CommerceTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @see \Drupal\simpletest\WebTestBase::installModulesFromClassProperty()
   *
   * @var array
   */
  public static $modules = ['block', 'commerce', 'field'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
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

    $this->drupalCreateAdminRole('administrator');
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'configure store',
    ];
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEqual($status, SAVED_NEW, new FormattableMarkup('Created %label entity %type.', [
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
   * @param array $field_values
   *   The field values.
   * @param array $expected_values
   *   The expected values.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages:
   *   use \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   *   to embed variables in the message text, not t().
   *   If left blank, a default message will be displayed.
   */
  protected function assertFieldValues(array $field_values, array $expected_values, $message = '') {
    $valid = TRUE;
    if (count($field_values) == count($expected_values)) {
      foreach ($expected_values as $value) {
        if (!in_array($value, $field_values)) {
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
