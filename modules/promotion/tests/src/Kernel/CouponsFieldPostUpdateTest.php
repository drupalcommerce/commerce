<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests coupon field definition updated to orders.
 *
 * @group commerce
 */
class CouponsFieldPostUpdateTest extends CommerceKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig([
      'profile',
      'commerce_order',
    ]);

    $this->user = $this->createUser();

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [],
    ]);
    $this->order->save();
  }

  /**
   * Tests that commerce_promotion_post_update_1 works.
   */
  public function testPostUpdate1() {
    $this->assertFalse($this->order->hasField('coupons'));

    $this->installModule('commerce_promotion');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $post_update_registry = $this->container->get('update.post_update_registry');
    foreach ($post_update_registry->getModuleUpdateFunctions('commerce_promotion') as $function) {
      $function();
    }

    $this->order = $this->reloadEntity($this->order);
    $this->assertTrue($this->order->hasField('coupons'));
  }

}
