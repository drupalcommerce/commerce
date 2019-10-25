<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests coupon storage.
 *
 * @group commerce
 */
class CouponStorageTest extends OrderKernelTestBase {

  /**
   * The coupon storage.
   *
   * @var \Drupal\commerce_promotion\CouponStorageInterface
   */
  protected $couponStorage;

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
    $this->installConfig(['commerce_promotion']);

    $this->couponStorage = $this->container->get('entity_type.manager')->getStorage('commerce_promotion_coupon');
  }

  /**
   * Loads a coupon by its code.
   */
  public function testLoadEnabledByCode() {
    $coupon_code = $this->randomMachineName();
    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => TRUE,
    ]);
    $coupon->save();

    $coupon_loaded = $this->couponStorage->loadEnabledByCode($coupon_code);
    $this->assertEquals($coupon->id(), $coupon_loaded->id());

    $coupon_code = $this->randomMachineName();
    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => FALSE,
    ]);
    $coupon->save();

    $coupon_loaded = $this->couponStorage->loadEnabledByCode($coupon_code);
    $this->assertEmpty($coupon_loaded);
  }

}
