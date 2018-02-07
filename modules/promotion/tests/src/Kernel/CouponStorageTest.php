<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests coupon storage.
 *
 * @group commerce
 */
class CouponStorageTest extends CommerceKernelTestBase {

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
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);

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
