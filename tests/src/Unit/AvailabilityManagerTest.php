<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\AvailabilityManager;
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
      ->willReturn(NULL);

    $second_checker = $mock_builder->getMock();
    $second_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(TRUE);
    $second_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(TRUE);

    $third_checker = $mock_builder->getMock();
    $third_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(FALSE);
    $third_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(FALSE);

    $fourth_checker = $mock_builder->getMock();
    $fourth_checker->expects($this->any())
      ->method('applies')
      ->with($entity)
      ->willReturn(TRUE);
    $fourth_checker->expects($this->any())
      ->method('check')
      ->with($entity, 1)
      ->willReturn(FALSE);

    $user = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $store = $this->getMock('Drupal\commerce_store\Entity\StoreInterface');
    $context = new Context($user, $store);

    $this->availabilityManager->addChecker($first_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertNotEmpty($result, 'The checked entity is available when a checker returns NULL.');

    $this->availabilityManager->addChecker($second_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertNotEmpty($result, 'The checked entity is available when no checkers return FALSE.');

    $this->availabilityManager->addChecker($third_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertNotEmpty($result, 'The checked entity is available when a checker that would return FALSE does not apply.');

    $this->availabilityManager->addChecker($fourth_checker);
    $result = $this->availabilityManager->check($entity, 1, $context);
    $this->assertEmpty($result, 'The checked entity is not available when a checker that returns FALSE applies');

    $expectedCheckers = [$first_checker, $second_checker, $third_checker, $fourth_checker];
    $checkers = $this->availabilityManager->getCheckers();
    $this->assertEquals($expectedCheckers, $checkers, 'The manager has the expected checkers');
  }

}
