<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Tests\CurrencyAdminTest.
 */

namespace Drupal\commerce_price\Tests;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\CurrencyImporter;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests the address format entity and UI.
 *
 * @group address
 */
class CurrencyAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'commerce_price',
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

    $this->adminUser = $this->drupalCreateUser([
      'configure store',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that currencies were correctly preloaded during install.
   */
  function testPreloadedCurrencies() {
    $preloaded_currencies = ['USD', 'EUR', 'GBP'];
    $existing_currencies = [];
    foreach ($preloaded_currencies as $currency_code) {
      if (Currency::load($currency_code)) {
        $existing_currencies[] = $currency_code;
      }
    }
    $this->assertEqual($existing_currencies, $preloaded_currencies, 'Currencies were imported at installation');
  }

}