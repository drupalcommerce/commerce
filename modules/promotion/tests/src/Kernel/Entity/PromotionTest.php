<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Entity;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_promotion\Entity\Promotion;
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
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);
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
   * @covers ::getCurrentUsage
   * @covers ::setCurrentUsage
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

    $promotion->setCurrentUsage(1);
    $this->assertEquals(1, $promotion->getCurrentUsage());

    $promotion->setUsageLimit(10);
    $this->assertEquals(10, $promotion->getUsageLimit());

    $promotion->setStartDate(new DrupalDateTime('2017-01-01'));
    $this->assertEquals('2017-01-01', $promotion->getStartDate()->format('Y-m-d'));

    $promotion->setEndDate(new DrupalDateTime('2017-01-31'));
    $this->assertEquals('2017-01-31', $promotion->getEndDate()->format('Y-m-d'));

    $promotion->setEnabled(TRUE);
    $this->assertEquals(TRUE, $promotion->isEnabled());
  }

}
