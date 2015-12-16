<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce\Unit\Resolver\ChainLocaleResolverTest.
 */

namespace Drupal\Tests\commerce\Unit\Resolver;

use Drupal\commerce\Resolver\ChainLocaleResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass  Drupal\commerce\Resolver\ChainLocaleResolver
 * @group commerce
 */
class ChainLocaleResolverTest extends UnitTestCase {

  /**
   * @var \Drupal\commerce\Resolver\ChainLocaleResolver
   */
  protected $chainLocaleResolver;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->chainLocaleResolver = new ChainLocaleResolver();
  }

  /**
   * ::covers addResolver
   * ::covers getResolvers
   * ::covers resolve
   */
  public function testResolver() {
    $mock_builder = $this->getMockBuilder('Drupal\commerce\Resolver\LocaleResolverInterface')
      ->disableOriginalConstructor();

    $first_resolver = $mock_builder->getMock();
    $first_resolver->expects($this->once())
      ->method('resolve');

    $second_resolver = $mock_builder->getMock();
    $second_resolver->expects($this->once())
      ->method('resolve')
      ->willReturn('mk_MK');

    $third_resolver = $mock_builder->getMock();
    $third_resolver->expects($this->never())
      ->method('resolve');

    $this->chainLocaleResolver->addResolver($first_resolver);
    $this->chainLocaleResolver->addResolver($second_resolver);
    $this->chainLocaleResolver->addResolver($third_resolver);

    $resolvers = $this->chainLocaleResolver->getResolvers();
    $this->assertEquals([$first_resolver, $second_resolver, $third_resolver], $resolvers);

    $result = $this->chainLocaleResolver->resolve();
    $this->assertEquals('mk_MK', $result);
  }

}
