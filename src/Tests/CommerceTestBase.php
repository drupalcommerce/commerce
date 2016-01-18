<?php

/**
 * @file
 * Contains \Drupal\commerce\Tests\CommerceTestBase.
 */

namespace Drupal\commerce\Tests;

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
  public static $modules = ['commerce', 'block'];

  /**
   * User with permission to configure store settings.
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

    $this->adminUser = $this->drupalCreateUser($this->defaultAdminUserPermissions());
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Returns the permissions for the admin user.
   *
   * @return array
   *   Array of permissions.
   */
  protected function defaultAdminUserPermissions() {
    return [
      'view the administration theme',
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
    $entity = \Drupal::service('entity_type.manager')
      ->getStorage($entity_type)
      ->create($values);
    $status = $entity->save();
    $this->assertEqual($status, SAVED_NEW, t('Created %label entity %type.', ['%label' => $entity->getEntityType()->getLabel(), '%type' => $entity->id()]));

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
      $message = 'Counts did not match';
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
