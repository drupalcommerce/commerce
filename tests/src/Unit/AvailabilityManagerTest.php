<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\AvailabilityManager;
use Drupal\commerce\AvailabilityResponse;
use Drupal\commerce\AvailabilityResponseAvailable;
use Drupal\commerce\AvailabilityResponseNeutral;
use Drupal\commerce\AvailabilityResponseUnavailable;
use Drupal\commerce\Context;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\AvailabilityManager
 * @group commerce
 */
class AvailabilityManagerTest extends UnitTestCase {

  /**
   * The availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManager
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
   * ::covers getCheckers
   * ::covers check.
   */
  public function testCheck() {
    $mock_builder = $this->getMockBuilder('Drupal\commerce\AvailabilityCheckerInterface')
      ->disableOriginalConstructor();
    $entity = $this->getMock('Drupal\commerce_product\Entity\ProductVariationInterface');

    $first_checker = $mock_builder->getMock();
    $first_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(TRUE);
    $first_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(AvailabilityResponse::neutral());

    $second_checker = $mock_builder->getMock();
    $second_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(TRUE);
    $second_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(AvailabilityResponse::available(0, 1));

    $third_checker = $mock_builder->getMock();
    $third_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(FALSE);
    $third_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(AvailabilityResponse::unavailable(0, 0));

    $fourth_checker = $mock_builder->getMock();
    $fourth_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(TRUE);
    $fourth_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(AvailabilityResponse::unavailable(0, 0));

    $user = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $store = $this->getMock('Drupal\commerce_store\Entity\StoreInterface');
    $context = new Context($user, $store);

    $this->availabilityManager->addChecker($first_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertInstanceOf(AvailabilityResponseNeutral::class, $result, 'The checked entity is available when the only checker returns neutral.');

    $this->availabilityManager->addChecker($second_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertInstanceOf(AvailabilityResponseAvailable::class, $result, 'The checked entity is available when no checkers return unavailable.');

    $this->availabilityManager->addChecker($third_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertInstanceOf(AvailabilityResponseAvailable::class, $result, 'The checked entity is available when a checker that would return unavailable does not apply.');

    $this->availabilityManager->addChecker($fourth_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertInstanceOf(AvailabilityResponseUnavailable::class, $result, 'The checked entity is not available when a checker that returns unavailable applies.');

    $expectedCheckers = [$first_checker, $second_checker, $third_checker, $fourth_checker];
    $checkers = $this->availabilityManager->getCheckers();
    $this->assertEquals($expectedCheckers, $checkers, 'The manager has the expected checkers');
  }

}
