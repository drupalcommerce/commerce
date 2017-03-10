<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests coupon validation constraints.
 *
 * @group commerce
 */
class CouponValidationTest extends EntityKernelTestBase {

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

    $this->installEntitySchema('commerce_promotion_coupon');
  }

  /**
   * Tests the coupon validation constraints.
   */
  public function testValidation() {
    $coupon_code = $this->randomMachineName();
    // Add a coupon.
    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => TRUE,
    ]);

    $violations = $coupon->validate();
    $this->assertEquals(count($violations), 0);
    $coupon->save();

    // Add other coupon with the same code.
    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => TRUE,
    ]);

    $violations = $coupon->validate();
    $this->assertEquals(count($violations), 1);
    $this->assertEquals($violations[0]->getPropertyPath(), 'code');
    $this->assertEquals($violations[0]->getMessage(), 'A coupon with code %value already exists. Enter a unique code.');

    // Try with a different code.
    $coupon->setCode($coupon_code . 'X');
    $violations = $coupon->validate();
    $this->assertEquals(count($violations), 0);
  }

}
