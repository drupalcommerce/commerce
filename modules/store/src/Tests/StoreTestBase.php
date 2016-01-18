<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Tests\StoreTestBase.
 */

namespace Drupal\commerce_store\Tests;

use Drupal\commerce\Tests\CommerceTestBase;

/**
 * Defines base class for commerce test cases.
 */
abstract class StoreTestBase extends CommerceTestBase {

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

}
