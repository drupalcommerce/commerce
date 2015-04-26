<?php

/**
 * @file
 * Definition of \Drupal\commerce_product\Tests\CommerceProductTestBase.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Defines base class for shortcut test cases.
 */
abstract class CommerceProductTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce',
    'commerce_store',
    'commerce_product',
    'commerce_price',
    'field',
    'field_ui',
    'options',
    'entity_reference'
  ];

  /**
   * User with permission to administer products.
   */
  protected $adminUser;

  /**
   * The product to test against
   */
  protected $product;

  /**
   * The store to test against
   */
  protected $commerce_store;

  protected function setUp() {
    parent::setUp();

    $currency_code = "USD";
    // If the default country has been set, detect currency_code.
    $default_country = \Drupal::config('system.date')->get('country.default');
    if ($default_country) {
      $countryRepository = new \CommerceGuys\Intl\Country\CountryRepository();
      $currency_code = $countryRepository->get($default_country)
        ->getCurrencyCode();

      /** @var \Drupal\commerce_price\CurrencyImporterInterface $currency_importer */
      $currency_importer = \Drupal::service('commerce_price.currency_importer');
      $entity = $currency_importer->importCurrency($currency_code);
      if ($entity) {
        $entity->save();
      }
    }
    $storeType = $this->createEntity('commerce_store_type', [
        'id' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
      ]
    );

    $name = strtolower($this->randomMachineName(8));

    $values = [
      'name' => $name,
      'uid' => 1,
      'mail' => \Drupal::config('system.site')->get('mail'),
      'type' => $storeType->id(),
      'default_currency' => $currency_code,
      'currencies' => [$currency_code],
    ];
    $this->commerce_store = entity_create("commerce_store", $values);
    $this->commerce_store->save();

    // Set as default store.
    \Drupal::configFactory()->getEditable('commerce_store.settings')
      ->set('default_store', $this->commerce_store->uuid())->save();

    $this->adminUser = $this->drupalCreateUser(
      [
        'administer products',
        'administer product types',
        'administer commerce_product fields',
        'access administration pages',
      ]
    );
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
      SafeMarkup::format('Created %label entity %type.', [
          '%label' => $entity->getEntityType()->getLabel(),
          '%type' => $entity->id()
        ]
      )
    );

    return $entity;
  }

}
