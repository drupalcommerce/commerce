<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_promotion\CouponCodePattern;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the coupon code generator.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\CouponCodeGenerator
 *
 * @group commerce
 */
class CouponCodeGeneratorTest extends OrderKernelTestBase {

  /**
   * The coupon code generator.
   *
   * @var \Drupal\commerce_promotion\CouponCodeGeneratorInterface
   */
  protected $couponCodeGenerator;

  /**
   * A set of numeric coupons with single digit patterns.
   *
   * @var \Drupal\commerce_promotion\Entity\CouponInterface[]
   */
  protected $numericCoupons;

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
    $this->installConfig(['commerce_order']);

    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.5',
        ],
      ],
    ]);
    $promotion->save();

    $this->numericCoupons = [];
    for ($i = 0; $i < 10; $i++) {
      $coupon = Coupon::create([
        'promotion_id' => $promotion->id(),
        'code' => 'COUPON' . $i,
        'usage_limit' => 1,
        'status' => 1,
      ]);
      $coupon->save();
      $this->numericCoupons[] = $coupon;
    }

    $this->couponCodeGenerator = $this->container->get('commerce_promotion.coupon_code_generator');
  }

  /**
   * Tests the validatePattern method.
   *
   * @covers ::validatePattern
   */
  public function testPatternValidityChecker() {
    // Numeric pattern length 1 is too short for coupon quantity > 10.
    $pattern = new CouponCodePattern('numeric', '', '', 1);
    $result = $this->couponCodeGenerator->validatePattern($pattern, 11);
    $this->assertFalse($result);

    // Numeric pattern length 1 is long enough for coupon quantity = 10.
    $result = $this->couponCodeGenerator->validatePattern($pattern, 10);
    $this->assertTrue($result);

    // Numeric pattern length 1 is long enough for coupon quantity < 10.
    $result = $this->couponCodeGenerator->validatePattern($pattern, 9);
    $this->assertTrue($result);

    // Numeric pattern length 8 is long enough for coupon quantity 1000.
    $pattern = new CouponCodePattern('numeric', '', '', 8);
    $result = $this->couponCodeGenerator->validatePattern($pattern, 1000);
    $this->assertTrue($result);
  }

  /**
   * Tests the code generator.
   *
   * @covers ::generateCodes
   */
  public function testCouponGenerator() {
    // Test numeric type pattern, length 10, 1 code.
    $pattern = new CouponCodePattern('numeric', '', '', 10);
    $result = $this->couponCodeGenerator->generateCodes($pattern, 1);
    $this->assertNotEmpty($result);
    $this->assertTrue(ctype_digit($result[0]));
    $this->assertEquals(strlen($result[0]), 10);

    // Test alphabetic type pattern, length 100, 10 codes.
    $pattern = new CouponCodePattern('alphabetic', '', '', 100);
    $result = $this->couponCodeGenerator->generateCodes($pattern, 10);
    $this->assertEquals(count($result), 10);
    $this->assertTrue(ctype_alpha($result[0]));
    $this->assertEquals(strlen($result[0]), 100);

    // Test alphanumeric type pattern, length 50, 25 codes.
    $pattern = new CouponCodePattern('alphanumeric', '', '', 50);
    $result = $this->couponCodeGenerator->generateCodes($pattern, 25);
    $this->assertEquals(count($result), 25);
    $this->assertTrue(ctype_alnum($result[0]));
    $this->assertEquals(strlen($result[0]), 50);

    // Test prefix and suffix options.
    $pattern = new CouponCodePattern('numeric', 'save', 'XX', 2);
    $result = $this->couponCodeGenerator->generateCodes($pattern, 1);
    $this->assertNotEmpty($result);
    $this->assertEquals(substr($result[0], 0, 4), 'save');
    $this->assertTrue(ctype_digit(substr($result[0], 4, 2)));
    $this->assertEquals(substr($result[0], 6), 'XX');

    // Test coupon code conflict.
    $pattern = new CouponCodePattern('numeric', 'COUPON', '', 1);
    $result = $this->couponCodeGenerator->generateCodes($pattern, 1);
    $this->assertEmpty($result);
  }

}
