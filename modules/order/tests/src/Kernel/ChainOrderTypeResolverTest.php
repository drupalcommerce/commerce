<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the chain order type resolver.
 *
 * @group commerce
 */
class ChainOrderTypeResolverTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system', 'field', 'options', 'user', 'entity',
    'views', 'address', 'profile', 'state_machine',
    'inline_entity_form', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_line_item');
    $this->installConfig('commerce_order');
    // A line item type that doesn't need a purchasable entity, for simplicity.
    LineItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * Tests resolving the order type.
   */
  public function testOrderTypeResolution() {
    $line_item = LineItem::create([
      'type' => 'test',
    ]);
    $line_item->save();

    /** @var \Drupal\commerce_order\Resolver\ChainOrderTypeResolverInterface $resolver */
    $resolver = \Drupal::service('commerce_order.chain_order_type_resolver');

    $order_type = $resolver->resolve($line_item);

    $this->assertEquals('default', $order_type);
  }

}
