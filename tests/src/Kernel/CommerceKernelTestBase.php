<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Provides a base class for Commerce kernel tests.
 */
abstract class CommerceKernelTestBase extends EntityKernelTestBase {

  use StoreCreationTrait;

  /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'datetime',
    'entity',
    'options',
    'inline_entity_form',
    'views',
    'commerce',
    'commerce_price',
    'commerce_store',
  ];

  /**
   * The default store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('commerce_currency');
    $this->installEntitySchema('commerce_store');
    $this->installConfig(['commerce_store']);

    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import('USD');

    $this->store = $this->createStore('Default store', 'admin@example.com');
    \Drupal::entityTypeManager()->getStorage('commerce_store')->markAsDefault($this->store);
  }

}
