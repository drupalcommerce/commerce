<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Entity;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemPercentageOff;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Promotion entity.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Entity\Promotion
 *
 * @group commerce
 */
class PromotionTest extends CommerceKernelTestBase {

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
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * @covers ::getName
   * @covers ::setName
   * @covers ::getDescription
   * @covers ::setDescription
   * @covers ::getOrderTypes
   * @covers ::setOrderTypes
   * @covers ::getOrderTypeIds
   * @covers ::setOrderTypeIds
   * @covers ::getStores
   * @covers ::setStores
   * @covers ::setStoreIds
   * @covers ::getStoreIds
   * @covers ::getOffer
   * @covers ::setOffer
   * @covers ::getConditionOperator
   * @covers ::setConditionOperator
   * @covers ::getCouponIds
   * @covers ::getCoupons
   * @covers ::setCoupons
   * @covers ::hasCoupons
   * @covers ::addCoupon
   * @covers ::removeCoupon
   * @covers ::hasCoupon
   * @covers ::getUsageLimit
   * @covers ::setUsageLimit
   * @covers ::getStartDate
   * @covers ::setStartDate
   * @covers ::getEndDate
   * @covers ::setEndDate
   * @covers ::isEnabled
   * @covers ::setEnabled
   */
  public function testPromotion() {
    $order_type = OrderType::load('default');
    $promotion = Promotion::create([
      'status' => FALSE,
    ]);

    $promotion->setName('My Promotion');
    $this->assertEquals('My Promotion', $promotion->getName());

    $promotion->setDescription('My Promotion Description');
    $this->assertEquals('My Promotion Description', $promotion->getDescription());

    $promotion->setOrderTypes([$order_type]);
    $order_types = $promotion->getOrderTypes();
    $this->assertEquals($order_type->id(), $order_types[0]->id());

    $promotion->setOrderTypeIds([$order_type->id()]);
    $this->assertEquals([$order_type->id()], $promotion->getOrderTypeIds());

    $promotion->setStores([$this->store]);
    $this->assertEquals([$this->store], $promotion->getStores());

    $promotion->setStoreIds([$this->store->id()]);
    $this->assertEquals([$this->store->id()], $promotion->getStoreIds());

    $rounder = $this->prophesize(RounderInterface::class)->reveal();
    $offer = new OrderItemPercentageOff(['percentage' => '0.5'], 'order_percentage_off', [], $rounder);
    $promotion->setOffer($offer);
    $this->assertEquals($offer->getPluginId(), $promotion->getOffer()->getPluginId());
    $this->assertEquals($offer->getConfiguration(), $promotion->getOffer()->getConfiguration());

    $this->assertEquals('AND', $promotion->getConditionOperator());
    $promotion->setConditionOperator('OR');
    $this->assertEquals('OR', $promotion->getConditionOperator());

    $coupon1 = Coupon::create([
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon1->save();
    $coupon2 = Coupon::create([
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon2->save();
    $coupon1 = Coupon::load($coupon1->id());
    $coupon2 = Coupon::load($coupon2->id());
    $coupons = [$coupon1, $coupon2];
    $coupon_ids = [$coupon1->id(), $coupon2->id()];

    $this->assertFalse($promotion->hasCoupons());
    $promotion->setCoupons($coupons);
    $this->assertTrue($promotion->hasCoupons());
    $this->assertEquals($coupons, $promotion->getCoupons());
    $this->assertEquals($coupon_ids, $promotion->getCouponIds());
    $this->assertTrue($promotion->hasCoupon($coupon1));
    $promotion->removeCoupon($coupon1);
    $this->assertFalse($promotion->hasCoupon($coupon1));
    $promotion->addCoupon($coupon1);
    $this->assertTrue($promotion->hasCoupon($coupon1));

    // Check Coupon::postDelete() remove Coupon reference from promotion.
    $promotion->save();
    $promotion = $this->reloadEntity($promotion);
    $this->assertEquals($promotion->id(), 1);
    $coupon1->delete();
    $this->assertFalse($promotion->hasCoupon($coupon1));

    $promotion->setUsageLimit(10);
    $this->assertEquals(10, $promotion->getUsageLimit());

    $promotion->setStartDate(new DrupalDateTime('2017-01-01'));
    $this->assertEquals('2017-01-01', $promotion->getStartDate()->format('Y-m-d'));

    $promotion->setEndDate(new DrupalDateTime('2017-01-31'));
    $this->assertEquals('2017-01-31', $promotion->getEndDate()->format('Y-m-d'));

    $promotion->setEnabled(TRUE);
    $this->assertEquals(TRUE, $promotion->isEnabled());
  }

  /**
   * @covers ::createDuplicate
   */
  public function testDuplicate() {
    $coupon = Coupon::create([
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $promotion = Promotion::create([
      'name' => '10% off',
      'coupons' => [$coupon],
      'status' => FALSE,
    ]);
    $promotion->save();
    $this->assertNotEmpty($promotion->getCouponIds());

    $duplicate_promotion = $promotion->createDuplicate();
    $this->assertEquals('10% off', $duplicate_promotion->label());
    $this->assertFalse($duplicate_promotion->hasCoupons());
  }

}
