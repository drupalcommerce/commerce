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
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line, $context) use (&$previous_error_handler) {
      $skipped_deprecations = [
        "Render #post_render callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::postRender. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725",
      ];
      if (!in_array($message, $skipped_deprecations, TRUE)) {
        return $previous_error_handler($severity, $message, $file, $line, $context);
      }
    }, E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    restore_error_handler();
    parent::tearDown();
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
