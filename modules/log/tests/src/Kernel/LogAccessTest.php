<?php

namespace Drupal\Tests\commerce_log\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_log\Entity\Log;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the log access control.
 *
 * @coversDefaultClass \Drupal\commerce_log\LogAccessControlHandler
 * @group commerce
 */
class LogAccessTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_log',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_log');

    $this->installConfig('commerce_order');

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
