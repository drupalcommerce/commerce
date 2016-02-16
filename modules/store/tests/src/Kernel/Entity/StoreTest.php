<?php
/**
 * @file
 * Contains \Drupal\Tests\commerce_store\Kernel\Entity\StoreTest.
 */

namespace Drupal\Tests\commerce_store\Kernel\Entity;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_store\Entity\Store;
use Drupal\user\Entity\User;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Store entity.
 *
 * @coversDefaultClass \Drupal\commerce_store\Entity\Store
 *
 * @group commerce
 */
class StoreTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'options',
    'user',
    'views',
    'address',
    'profile',
    'state_machine',
    'commerce',
    'commerce_store',
    'commerce_price',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup schema's.
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
  }

  /**
   * Tests commerce Store Interface.
   *
   * @covers ::id
   * @covers ::getName
   * @covers ::getOwner
   * @covers ::getOwnerId
   * @covers ::setOwner
   * @covers ::setOwnerId
   * @covers ::getEmail
   * @covers ::setEmail
   * @covers ::getDefaultCurrency
   * @covers ::setDefaultCurrency
   * @covers ::getDefaultCurrencyCode
   * @covers ::setDefaultCurrencyCode
   * @covers ::getAddress
   */
  public function testStore() {
    // Create two currencies.
    // Euro.
    $euro_currency = Currency::create([
      'currencyCode' => 'EUR',
      'name' => 'Euro',
      'numericCode' => 123,
      'symbol' => '€',
      'fractionDigits' => 2,
    ]);
    $euro_currency->save();
    // Dollar.
    $dollar_currency = Currency::create([
      'currencyCode' => 'USD',
      'name' => 'Dollar',
      'numericCode' => 139,
      'symbol' => '$',
      'fractionDigits' => 2,
    ]);
    $dollar_currency->save();

    // Create a user.
    $user = User::create([
      'name' => 'test',
      'uid' => 1,
      'status' => TRUE,
    ]);
    $user->save();

    $store_address = [
      'country' => 'FR',
      'postal_code' => '75003',
      'locality' => 'Paris',
      'address_line1' => 'A different french street',
    ];

    // Create a store.
    $store = Store::create([
      'type' => 'default',
      'name' => 'My fancy store',
      'store_id' => 15,
      'uid' => $user->id(),
      'mail' => 'info@store.mail',
      'default_currency' => $euro_currency,
      'address' => $store_address,
      'billing_countries' => ['FR'],
    ]);
    $store->save();

    // Check if the id matches.
    $this->assertEquals($store->id(), 15);

    // Store: test getName() and setName().
    $this->assertEquals($store->getName(), 'My fancy store');
    $store->setName('My normal store');
    $store->save();
    $this->assertEquals($store->getName(), 'My normal store');

    // Store: test getOwner() and getOwnerId().
    $this->assertEquals($store->getOwner()->id(), $user->id());
    $this->assertEquals($store->getOwnerId(), $user->id());

    // Create a new user.
    $new_user = User::create([
      'name' => 'newuser',
      'uid' => 2,
      'status' => TRUE,
    ]);
    $new_user->save();

    // Store: test setOwner() and retest getOwner() and getOwnerId().
    $store->setOwner($new_user);
    $store->save();
    $this->assertEquals($store->getOwner()->id(), $new_user->id());
    $this->assertEquals($store->getOwnerId(), $new_user->id());

    // Store: test setOwnerId() and retest getOwner() and getOwnerId().
    $store->setOwnerID($user->id());
    $store->save();
    $this->assertEquals($store->getOwner()->id(), $user->id());
    $this->assertEquals($store->getOwnerId(), $user->id());

    // Store: test getEmail() and getEmail().
    $this->assertEquals($store->getEmail(), 'info@store.mail');
    $store->setEmail('sales@store.mail');
    $store->save();
    $this->assertEquals($store->getEmail(), 'sales@store.mail');

    // Store: test getDefaultCurrency() and setDefaultCurrency().
    $this->assertEquals($store->getDefaultCurrency()->getCurrencyCode(), $euro_currency->getCurrencyCode());
    $store->setDefaultCurrency($dollar_currency);
    $store->save();
    $this->assertEquals($store->getDefaultCurrency()->getCurrencyCode(), $dollar_currency->getCurrencyCode());

    // Store: test getDefaultCurrencyCode() and setDefaultCurrencyCode().
    $this->assertEquals($store->getDefaultCurrencyCode(), $dollar_currency->getCurrencyCode());
    $store->setDefaultCurrencyCode($euro_currency->getCurrencyCode());
    $store->save();
    $this->assertEquals($store->getDefaultCurrencyCode(), $euro_currency->getCurrencyCode());

    // TODO: Store: test getAddress().
  }
}
