<?php

namespace Drupal\Tests\commerce_order\Unit;

use Drupal\commerce\AvailabilityCheckerInterface as LegacyAvailabilityCheckerInterface;
use Drupal\commerce_order\AvailabilityCheckerInterface;
use Drupal\commerce_order\AvailabilityManager;
use Drupal\commerce\Context;
use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_order\AvailabilityManager
 * @group commerce
 */
class AvailabilityManagerTest extends UnitTestCase {

  /**
   * The availability manager.
   *
   * @var \Drupal\commerce_order\AvailabilityManager
   */
  protected $availabilityManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->availabilityManager = new AvailabilityManager();
  }

  /**
   * ::covers addChecker
   * ::covers check.
   */
  public function testCheck() {
    $mock_builder = $this->getMockBuilder(AvailabilityCheckerInterface::class)
      ->disableOriginalConstructor();
    $order_item = $this->createMock(OrderItemInterface::class);

    $first_checker = $mock_builder->getMock();
    $first_checker->expects($this->any())
      ->method('applies')
      ->with($order_item)
      ->willReturn(TRUE);
    $first_checker->expects($this->any())
      ->method('check')
      ->with($order_item)
      ->willReturn(NULL);

    $second_checker = $mock_builder->getMock();
    $second_checker->expects($this->any())
      ->method('applies')
      ->with($order_item)
      ->willReturn(TRUE);
    $second_checker->expects($this->any())
      ->method('check')
      ->with($order_item)
      ->willReturn(AvailabilityResult::neutral());

    $third_checker = $mock_builder->getMock();
    $third_checker->expects($this->any())
      ->method('applies')
      ->with($order_item)
      ->willReturn(FALSE);
    $third_checker->expects($this->any())
      ->method('check')
      ->with($order_item)
      ->willReturn(AvailabilityResult::unavailable());

    $fourth_checker = $mock_builder->getMock();
    $fourth_checker->expects($this->any())
      ->method('applies')
      ->with($order_item)
      ->willReturn(TRUE);
    $fourth_checker->expects($this->any())
      ->method('check')
      ->with($order_item)
      ->willReturn(AvailabilityResult::unavailable('The product is not available'));

    $user = $this->createMock(AccountInterface::class);
    $store = $this->createMock(StoreInterface::class);
    $context = new Context($user, $store);

    // Test the new availability checkers first.
    $this->availabilityManager->addChecker($first_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::neutral(), $result, 'The checked order item is available when a checker returns NULL.');

    $this->availabilityManager->addChecker($second_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::neutral(), $result, 'The checked order item is available when a checker returns a "neutral" availability result.');

    $this->availabilityManager->addChecker($third_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::neutral(), $result, 'The checked order item is available when a checker that would return an "unavailable" availability result does not apply.');

    $this->availabilityManager->addChecker($fourth_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::unavailable('The product is not available'), $result, 'The checked order item is not available when a checker returning an"unavailable" availability result applies.');
  }

  /**
   * ::covers addChecker
   * ::covers addLegacyChecker
   * ::covers check.
   *
   * @group legacy
   */
  public function testLegacyCheckers() {
    $order_item = $this->createMock(OrderItemInterface::class);
    $product_variation = $this->createMock(ProductVariationInterface::class);
    $order_item
      ->method('getPurchasedEntity')
      ->willReturn($product_variation);
    $order_item
      ->method('getQuantity')
      ->willReturn(1);
    $legacy_mock_builder = $this->getMockBuilder(LegacyAvailabilityCheckerInterface::class)
      ->disableOriginalConstructor();
    $first_legacy_checker = $legacy_mock_builder->getMock();
    $first_legacy_checker->expects($this->any())
      ->method('applies')
      ->with($product_variation)
      ->willReturn(TRUE);
    $first_legacy_checker->expects($this->any())
      ->method('check')
      ->with($product_variation, 1)
      ->willReturn(NULL);

    $second_legacy_checker = $legacy_mock_builder->getMock();
    $second_legacy_checker->expects($this->any())
      ->method('applies')
      ->with($product_variation)
      ->willReturn(TRUE);
    $second_legacy_checker->expects($this->any())
      ->method('check')
      ->with($product_variation, 1)
      ->willReturn(TRUE);

    $third_legacy_checker = $legacy_mock_builder->getMock();
    $third_legacy_checker->expects($this->any())
      ->method('applies')
      ->with($product_variation)
      ->willReturn(TRUE);
    $third_legacy_checker->expects($this->any())
      ->method('check')
      ->with($product_variation, 1)
      ->willReturn(FALSE);

    $user = $this->createMock(AccountInterface::class);
    $store = $this->createMock(StoreInterface::class);
    $context = new Context($user, $store);
    $this->availabilityManager->addLegacyChecker($first_legacy_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::neutral(), $result, 'The checked order item is available when a legacy checker returns NULL.');

    $this->availabilityManager->addLegacyChecker($second_legacy_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::neutral(), $result, 'The checked order item is available when a legacy checker returns TRUE.');

    $this->availabilityManager->addLegacyChecker($third_legacy_checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::unavailable(), $result, 'The checked order item is unavailable when a legacy checker returns FALSE.');

    // Test the integration with both legacy checkers and new checkers.
    $mock_builder = $this->getMockBuilder(AvailabilityCheckerInterface::class)
      ->disableOriginalConstructor();
    $checker = $mock_builder->getMock();
    $checker->expects($this->any())
      ->method('applies')
      ->with($order_item)
      ->willReturn(TRUE);
    $checker->expects($this->any())
      ->method('check')
      ->with($order_item)
      ->willReturn(AvailabilityResult::unavailable('The product is not available'));
    $this->availabilityManager->addChecker($checker);
    $result = $this->availabilityManager->check($order_item, $context);
    $this->assertEquals(AvailabilityResult::unavailable('The product is not available'), $result, 'The checked order item is unavailable when a new checker returns an "unavailable" availability result.');
  }

}
