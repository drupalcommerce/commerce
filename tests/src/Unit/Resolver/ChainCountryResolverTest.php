<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce\Unit\Resolver\ChainCountryResolverTest.
 */

namespace Drupal\Tests\commerce\Unit\Resolver;

use Drupal\commerce\Resolver\ChainCountryResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass  Drupal\commerce\Resolver\ChainCountryResolver
 * @group commerce
 */
class ChainCountryResolverTest extends UnitTestCase {

  /**
   * @var \Drupal\commerce\Resolver\ChainCountryResolver
   */
  protected $chainCountryResolver;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
      parent::setUp();
      $this->chainCountryResolver = new ChainCountryResolver();
  }

  /**
   * ::covers addResolver
   * ::covers getResolvers
   * ::covers resolve
   */
  public function testResolver() {
    $mock_builder = $this->getMockBuilder('Drupal\commerce\Resolver\CountryResolverInterface')
      ->disableOriginalConstructor();

    $first_resolver = $mock_builder->getMock();
    $first_resolver->expects($this->once())
      ->method('resolve');

    $second_resolver = $mock_builder->getMock();
    $second_resolver->expects($this->once())
      ->method('resolve')
      ->willReturn('RS');

    $third_resolver = $mock_builder->getMock();
    $third_resolver->expects($this->never())
      ->method('resolve');

    $this->chainCountryResolver->addResolver($first_resolver);
    $this->chainCountryResolver->addResolver($second_resolver);
    $this->chainCountryResolver->addResolver($third_resolver);

    $resolvers =  $this->chainCountryResolver->getResolvers();
    $this->assertEquals([$first_resolver, $second_resolver, $third_resolver], $resolvers);

    $result = $this->chainCountryResolver->resolve();
    $this->assertEquals('RS', $result);
  }

}

