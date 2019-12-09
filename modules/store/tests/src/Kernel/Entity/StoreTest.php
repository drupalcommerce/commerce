<?php

namespace Drupal\Tests\commerce_store\Kernel\Entity;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_store\Entity\Store;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Store entity.
 *
 * @coversDefaultClass \Drupal\commerce_store\Entity\Store
 *
 * @group commerce
 */
class StoreTest extends CommerceKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * @covers ::getName
   * @covers ::setName
   * @covers ::getEmail
   * @covers ::setEmail
   * @covers ::getDefaultCurrency
   * @covers ::setDefaultCurrency
   * @covers ::getDefaultCurrencyCode
   * @covers ::setDefaultCurrencyCode
   * @covers ::getTimezone
   * @covers ::setTimezone
   * @covers ::getBillingCountries
   * @covers ::setBillingCountries
   * @covers ::isDefault
   * @covers ::setDefault
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   */
  public function testStore() {
    $store = Store::create([
      'type' => 'online',
    ]);

    $store->setName('French store');
    $this->assertEquals('French store', $store->getName());

    $store->setEmail('owner@example.com');
    $this->assertEquals('owner@example.com', $store->getEmail());

    $store->setDefaultCurrencyCode('USD');
    $this->assertEquals('USD', $store->getDefaultCurrencyCode());

    $currency = Currency::load('USD');
    $store->setDefaultCurrency($currency);
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $default_currency */
    $default_currency = $store->getDefaultCurrency();
    $this->assertNotEmpty($default_currency);
    $this->assertEquals($currency->id(), $default_currency->id());
    $this->assertEquals($currency->id(), $store->getDefaultCurrencyCode());
    $store->setDefaultCurrencyCode('INVALID');
    $this->assertEquals(NULL, $store->getDefaultCurrency());
    $store->setDefaultCurrencyCode('USD');
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $default_currency */
    $default_currency = $store->getDefaultCurrency();
    $this->assertNotEmpty($default_currency);
    $this->assertEquals('USD', $default_currency->id());
    $this->assertEquals('USD', $store->getDefaultCurrencyCode());

    $store->setTimezone('Europe/Paris');
    $this->assertEquals('Europe/Paris', $store->getTimezone());

    $store->setBillingCountries(['FR', 'DE']);
    $this->assertEquals(['FR', 'DE'], $store->getBillingCountries());

    $store->setDefault(TRUE);
    $this->assertTrue($store->isDefault());

    $store->setOwner($this->user);
    $this->assertEquals($this->user, $store->getOwner());
    $this->assertEquals($this->user->id(), $store->getOwnerId());
    $store->setOwnerId(0);
    $this->assertEquals(NULL, $store->getOwner());
    $store->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $store->getOwner());
    $this->assertEquals($this->user->id(), $store->getOwnerId());
  }

  /**
   * Tests default store functionality.
   */
  public function testDefaultStore() {
    $this->store->delete();
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store1 */
    $store1 = Store::create([
      'type' => 'online',
      'uid' => $this->user->id(),
      'is_default' => TRUE,
    ]);
    $store1->save();

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store2 */
    $store2 = Store::create([
      'type' => 'online',
      'uid' => $this->user->id(),
      'is_default' => TRUE,
    ]);
    $store2->save();
    $this->assertTrue($store2->isDefault());

    // Confirm that setting the second store as default removed the
    // flag from the first store.
    $store2 = $this->reloadEntity($store2);
    $store1 = $this->reloadEntity($store1);
    $this->assertTrue($store2->isDefault());
    $this->assertFalse($store1->isDefault());

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store2 */
    $store3 = Store::create([
      'type' => 'online',
      'uid' => $this->user->id(),
    ]);
    $store3->save();
    $this->assertFalse($store3->isDefault());

    // Test fallback.
    $store2->setDefault(FALSE);
    $store2->save();
    $this->assertFalse($store2->isDefault());
    /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
    $store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    $default_store = $store_storage->loadDefault();
    $this->assertNotEmpty($default_store);
    $this->assertEquals($store3->id(), $default_store->id());
  }

}
