<?php

namespace Drupal\Tests\commerce_log\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_log\Entity\Log;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the log access control.
 *
 * @coversDefaultClass \Drupal\commerce_log\LogAccessControlHandler
 * @group commerce
 */
class LogAccessTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_log',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_log');

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    $order = Order::create([
      'type' => 'default',
      'state' => 'canceled',
    ]);
    $order->save();
    /** @var \Drupal\commerce_log\Entity\LogInterface $log */
    $log = Log::create([
      'category_id' => 'commerce_order',
      'template_id' => 'order_canceled',
      'source_entity_id' => $order->id(),
      'source_entity_type' => 'commerce_order',
      'params' => [],
    ]);
    $log->save();

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($log->access('view', $account));
    $this->assertFalse($log->access('update', $account));
    $this->assertFalse($log->access('delete', $account));

    $account = $this->createUser([], ['view commerce_order']);
    $this->assertTrue($log->access('view', $account));
    $this->assertFalse($log->access('update', $account));
    $this->assertFalse($log->access('delete', $account));

    $account = $this->createUser([], ['update default commerce_order']);
    $this->assertFalse($log->access('view', $account));
    $this->assertTrue($log->access('update', $account));
    $this->assertTrue($log->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_order']);
    $this->assertTrue($log->access('view', $account));
    $this->assertTrue($log->access('update', $account));
    $this->assertTrue($log->access('delete', $account));

    // Broken source reference.
    $log->set('source_entity_id', '999');
    $account = $this->createUser([], ['update default commerce_order']);
    $this->assertFalse($log->access('view', $account));
    $this->assertFalse($log->access('update', $account));
    $this->assertFalse($log->access('delete', $account));
  }

}
