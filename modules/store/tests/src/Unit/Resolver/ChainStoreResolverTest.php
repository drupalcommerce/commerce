<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce_store\Unit\Resolver\ChainStoreResolverTest.
 */

namespace Drupal\Tests\commerce_store\Unit\Resolver;

use Drupal\commerce_store\Resolver\ChainStoreResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_store\Resolver\ChainStoreResolver
 * @group commerce_store
 */
class ChainStoreResolverTest extends UnitTestCase {

  /**
   * The resolver.
   *
   * @var \Drupal\commerce_store\Resolver\ChainStoreResolver
   */
  protected $resolver;

  /**
  * {@inheritdoc}
  */
  public function setUp() {
    parent::setUp();
    $this->resolver = new ChainStoreResolver();
  }

  /**
   * ::covers addResolver
   * ::covers getResolvers
   * ::covers resolve
   */
  public function testResolver() {
    $mock_builder = $this->getMockBuilder('Drupal\commerce_store\Resolver\StoreResolverInterface')
      ->disableOriginalConstructor();

    $first_resolver = $mock_builder->getMock();
    $first_resolver->expects($this->once())
      ->method('resolve');

    $second_resolver = $mock_builder->getMock();
    $second_resolver->expects($this->once())
      ->method('resolve')
      ->willReturn('testStore');

    $third_resolver = $mock_builder->getMock();
    $third_resolver->expects($this->never())
      ->method('resolve');

    $this->resolver->addResolver($first_resolver);
    $this->resolver->addResolver($second_resolver);
    $this->resolver->addResolver($third_resolver);

    $resolvers =  $this->resolver->getResolvers();
    $this->assertEquals([$first_resolver, $second_resolver, $third_resolver], $resolvers);

    $result = $this->resolver->resolve();
    $this->assertEquals('testStore', $result);
  }

}
