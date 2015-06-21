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
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('default_store')
      ->will($this->returnValue('fakeuuid'));

    $configFactory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $configFactory->expects($this->once())
      ->method('get')
      ->with('commerce_store.settings')
      ->will($this->returnValue($config));

    $entityManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entityManager->expects($this->once())
      ->method('loadEntityByUuid')
      ->with('commerce_store', 'fakeuuid')
      ->will($this->returnValue('testStore'));

    $this->resolver = new DefaultStoreResolver($configFactory, $entityManager);
  }

  /**
   * @covers ::resolve
   */
  public function testResolve() {
    $defaultStore = $this->resolver->resolve();
    $this->assertEquals('testStore', $defaultStore);
  }

}
