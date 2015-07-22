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
    $mockupBuilder = $this->getMockBuilder('Drupal\commerce_store\Resolver\StoreResolverInterface')
      ->disableOriginalConstructor();

    $firstResolver = $mockupBuilder->getMock();
    $firstResolver->expects($this->once())
      ->method('resolve');

    $secondResolver = $mockupBuilder->getMock();
    $secondResolver->expects($this->once())
      ->method('resolve')
      ->willReturn('testStore');

    $thirdResolver = $mockupBuilder->getMock();
    $thirdResolver->expects($this->never())
      ->method('resolve');

    $this->resolver->addResolver($firstResolver);
    $this->resolver->addResolver($secondResolver);
    $this->resolver->addResolver($thirdResolver);

    $resolvers =  $this->resolver->getResolvers();
    $this->assertEquals([$firstResolver, $secondResolver, $thirdResolver], $resolvers);

    $result = $this->resolver->resolve();
    $this->assertEquals('testStore', $result);
  }

}
