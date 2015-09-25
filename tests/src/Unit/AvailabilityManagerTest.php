<?php

/**
 * @file
 * Contains \Drupal\commerce\Tests\AvailabilityManagerTest.
 */

namespace Drupal\commerce\Tests;

use Drupal\commerce\AvailabilityManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\AvailabilityManager
 * @group commerce
 */
class AvailabilityManagerTest extends UnitTestCase {

  /**
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
   * ::covers check
   */
  public function testCheck() {
    $mockCheckerBuilder = $this->getMockBuilder('Drupal\commerce\AvailabilityCheckerInterface')
      ->disableOriginalConstructor();
    $mockEntity = $this->getMock('Drupal\commerce_product\Entity\ProductVariationInterface');

    $firstChecker = $mockCheckerBuilder->getMock();
    $firstChecker->expects($this->any())
      ->method('applies')
      ->with($mockEntity)
      ->willReturn(TRUE);
    $firstChecker->expects($this->any())
      ->method('check')
      ->with($mockEntity, 1)
      ->willReturn(NULL);

    $secondChecker = $mockCheckerBuilder->getMock();
    $secondChecker->expects($this->any())
      ->method('applies')
      ->with($mockEntity)
      ->willReturn(TRUE);
    $secondChecker->expects($this->any())
      ->method('check')
      ->with($mockEntity, 1)
      ->willReturn(TRUE);

    $thirdChecker = $mockCheckerBuilder->getMock();
    $thirdChecker->expects($this->any())
      ->method('applies')
      ->with($mockEntity)
      ->willReturn(FALSE);
    $thirdChecker->expects($this->any())
      ->method('check')
      ->with($mockEntity, 1)
      ->willReturn(FALSE);

    $fourthChecker = $mockCheckerBuilder->getMock();
    $fourthChecker->expects($this->any())
      ->method('applies')
      ->with($mockEntity)
      ->willReturn(TRUE);
    $fourthChecker->expects($this->any())
      ->method('check')
      ->with($mockEntity, 1)
      ->willReturn(FALSE);

    $this->availabilityManager->addChecker($firstChecker);
    $result = $this->availabilityManager->check($mockEntity, 1);
    $this->assertTrue($result, 'The checked entity is available when a checker returns NULL.');

    $this->availabilityManager->addChecker($secondChecker);
    $result = $this->availabilityManager->check($mockEntity, 1);
    $this->assertTrue($result, 'The checked entity is available when no checkers return FALSE.');

    $this->availabilityManager->addChecker($thirdChecker);
    $result = $this->availabilityManager->check($mockEntity, 1);
    $this->assertTrue($result, 'The checked entity is available when a checker that would return FALSE does not apply.');

    $this->availabilityManager->addChecker($fourthChecker);
    $result = $this->availabilityManager->check($mockEntity, 1);
    $this->assertFalse($result, 'The checked entity is not available when a checker that returns FALSE applies');

    $expectedCheckers = [$firstChecker, $secondChecker, $thirdChecker, $fourthChecker];
    $checkers = $this->availabilityManager->getCheckers();
    $this->assertEquals($expectedCheckers, $checkers, 'The manager has the expected checkers');
  }

}
