<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce_store\Unit\Resolver\DefaultStoreResolver.
 */

namespace Drupal\Tests\commerce_store\Unit\Resolver;

use Drupal\commerce_store\Resolver\DefaultStoreResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_store\Resolver\DefaultStoreResolver
 * @group commerce_store
 */
class DefaultStoreResolverTest extends UnitTestCase {

  /**
   * The resolver.
   *
   * @var \Drupal\commerce_store\Resolver\DefaultStoreResolver
   */
  protected $resolver;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $storage = $this->getMockBuilder('Drupal\commerce_store\StoreStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $storage->expects($this->once())
      ->method('loadDefault')
      ->willReturn('testStore');

    $entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entityManager->expects($this->once())
      ->method('getStorage')
      ->with('commerce_store')
      ->willReturn($storage);

    $this->resolver = new DefaultStoreResolver($entityManager);
  }

  /**
   * @covers ::resolve
   */
  public function testResolve() {
    $defaultStore = $this->resolver->resolve();
    $this->assertEquals('testStore', $defaultStore);
  }

}
