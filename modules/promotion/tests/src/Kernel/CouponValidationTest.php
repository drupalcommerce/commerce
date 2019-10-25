<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests coupon validation constraints.
 *
 * @group commerce
 */
class CouponValidationTest extends OrderKernelTestBase {

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
   * Tests the coupon code constraint.
   */
  public function testUniqueness() {
    $coupon_code = $this->randomMachineName();
    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => TRUE,
    ]);
    $violations = $coupon->validate();
    $this->assertEquals(count($violations), 0);
    $coupon->save();

    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => TRUE,
    ]);
    $expected_message = new FormattableMarkup('The coupon code %value is already in use and must be unique.', [
      '%value' => $coupon_code,
    ]);
    $violations = $coupon->validate();
    $this->assertEquals(count($violations), 1);
    $this->assertEquals($violations[0]->getPropertyPath(), 'code');
    $this->assertEquals($violations[0]->getMessage(), $expected_message->__toString());

    $coupon->setCode($coupon_code . 'X');
    $violations = $coupon->validate();
    $this->assertEquals(count($violations), 0);
  }

}
