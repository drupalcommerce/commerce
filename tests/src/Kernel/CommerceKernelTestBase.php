<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\commerce_price\Comparator\NumberComparator;
use Drupal\commerce_price\Comparator\PriceComparator;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\commerce\Traits\DeprecationSuppressionTrait;
use SebastianBergmann\Comparator\Factory as PhpUnitComparatorFactory;

/**
 * Provides a base class for Commerce kernel tests.
 */
abstract class CommerceKernelTestBase extends EntityKernelTestBase {

  use DeprecationSuppressionTrait;
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
    'path',
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
    $this->setErrorHandler();

    $factory = PhpUnitComparatorFactory::getInstance();
    $factory->register(new NumberComparator());
    $factory->register(new PriceComparator());

    if (\Drupal::entityTypeManager()->hasDefinition('path_alias')) {
      $this->installEntitySchema('path_alias');
    }
    $this->installEntitySchema('commerce_currency');
    $this->installEntitySchema('commerce_store');
    $this->installConfig(['commerce_store']);

    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->import('USD');

    $this->store = $this->createStore('Default store', 'admin@example.com');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->restoreErrorHandler();
    parent::tearDown();
  }

}
