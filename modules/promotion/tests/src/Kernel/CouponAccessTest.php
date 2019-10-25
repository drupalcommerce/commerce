<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the coupon access control.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\CouponAccessControlHandler
 * @group commerce
 */
class CouponAccessTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
    ]);
    $promotion->save();
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
    $coupon = Coupon::create([
      'promotion_id' => $promotion->id(),
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon->save();

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($coupon->access('view', $account));
    $this->assertFalse($coupon->access('update', $account));
    $this->assertFalse($coupon->access('delete', $account));

    $account = $this->createUser([], ['view commerce_promotion']);
    $this->assertTrue($coupon->access('view', $account));
    $this->assertFalse($coupon->access('update', $account));
    $this->assertFalse($coupon->access('delete', $account));

    $account = $this->createUser([], ['update commerce_promotion']);
    $this->assertFalse($coupon->access('view', $account));
    $this->assertTrue($coupon->access('update', $account));
    $this->assertTrue($coupon->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_promotion']);
    $this->assertTrue($coupon->access('view', $account));
    $this->assertTrue($coupon->access('update', $account));
    $this->assertTrue($coupon->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_promotion_coupon');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('test', $account));

    $account = $this->createUser([], ['update commerce_promotion']);
    $this->assertTrue($access_control_handler->createAccess('test', $account));

    $account = $this->createUser([], ['administer commerce_promotion']);
    $this->assertTrue($access_control_handler->createAccess('test', $account));
  }

}
