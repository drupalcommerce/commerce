<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\AvailabilityManagerTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\AvailabilityManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the availability manager.
 *
 * @group commerce
 */
class AvailabilityManagerTest extends UnitTestCase {

  /**
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
   * ::covers check
   */
  public function testCheck() {
    $mockCheckerBuilder = $this->getMockBuilder('Drupal\commerce_order\AvailabilityCheckerInterface')
      ->disableOriginalConstructor();

    $mockSource = $this->getMock('Drupal\commerce_product\ProductVariationInterface');

    $firstChecker = $mockCheckerBuilder->getMock();
    $firstChecker->expects($this->any())
      ->method('applies')
      ->with($mockSource)
      ->willReturn(TRUE);
    $firstChecker->expects($this->any())
      ->method('check')
      ->with($mockSource, 1)
      ->willReturn(NULL);

    $secondChecker = $mockCheckerBuilder->getMock();
    $secondChecker->expects($this->any())
      ->method('applies')
      ->with($mockSource)
      ->willReturn(TRUE);
    $secondChecker->expects($this->any())
      ->method('check')
      ->with($mockSource, 1)
      ->willReturn(TRUE);

    $thirdChecker = $mockCheckerBuilder->getMock();
    $thirdChecker->expects($this->any())
      ->method('applies')
      ->with($mockSource)
      ->willReturn(FALSE);
    $thirdChecker->expects($this->any())
      ->method('check')
      ->with($mockSource, 1)
      ->willReturn(FALSE);

    $fourthChecker = $mockCheckerBuilder->getMock();
    $fourthChecker->expects($this->any())
      ->method('applies')
      ->with($mockSource)
      ->willReturn(TRUE);
    $fourthChecker->expects($this->any())
      ->method('check')
      ->with($mockSource, 1)
      ->willReturn(FALSE);

    $this->availabilityManager->addAvailabilityChecker($firstChecker);

    $result = $this->availabilityManager->check($mockSource, 1);
    $this->assertTrue($result, 'The checked source is available when a checker returns NULL.');

    $this->availabilityManager->addAvailabilityChecker($secondChecker);

    $result = $this->availabilityManager->check($mockSource, 1);
    $this->assertTrue($result, 'The checked source is available when no checkers return FALSE.');

    $this->availabilityManager->addAvailabilityChecker($thirdChecker);

    $result = $this->availabilityManager->check($mockSource, 1);
    $this->assertTrue($result, 'The checked source is available when a checker that would return FALSE does not apply to the given source.');

    $this->availabilityManager->addAvailabilityChecker($fourthChecker);

    $result = $this->availabilityManager->check($mockSource, 1);
    $this->assertFalse($result, 'The checked source is not available when a checker that returns FALSE applies to the given source.');
  }

}
