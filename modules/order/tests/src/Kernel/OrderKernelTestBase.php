<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Comparator\AdjustmentComparator;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use SebastianBergmann\Comparator\Factory as PhpUnitComparatorFactory;

/**
 * Provides a base class for order kernel tests.
 */
abstract class OrderKernelTestBase extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_number_pattern',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    PhpUnitComparatorFactory::getInstance()->register(new AdjustmentComparator());

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $this->installSchema('commerce_number_pattern', ['commerce_number_pattern_sequence']);

    // An order item type that doesn't need a purchasable entity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

}
