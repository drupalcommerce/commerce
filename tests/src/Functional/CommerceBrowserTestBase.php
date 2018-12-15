<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\commerce\Traits\CommerceBrowserTestTrait;

/**
 * Provides a base class for Commerce functional tests.
 */
abstract class CommerceBrowserTestBase extends BrowserTestBase {

  use BlockCreationTrait;
  use StoreCreationTrait;
  use CommerceBrowserTestTrait;

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

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
  public static $modules = [
    'system',
    'block',
    'field',
    'commerce',
    'commerce_price',
    'commerce_store',
  ];

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

    $this->store = $this->createStore();
    $this->placeBlock('local_tasks_block');
    $this->placeBlock('local_actions_block');
    $this->placeBlock('page_title_block');

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
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
    ];
  }

}
