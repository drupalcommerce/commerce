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
    $mockupBuilder = $this->getMockBuilder('Drupal\commerce\Resolver\CountryResolverInterface')
      ->disableOriginalConstructor();

    $firstResolver = $mockupBuilder->getMock();
    $firstResolver->expects($this->once())
      ->method('resolve');

    $secondResolver = $mockupBuilder->getMock();
    $secondResolver->expects($this->once())
      ->method('resolve')
      ->willReturn('RS');

    $thirdResolver = $mockupBuilder->getMock();
    $thirdResolver->expects($this->never())
      ->method('resolve');

    $this->chainCountryResolver->addResolver($firstResolver);
    $this->chainCountryResolver->addResolver($secondResolver);
    $this->chainCountryResolver->addResolver($thirdResolver);

    $resolvers =  $this->chainCountryResolver->getResolvers();
    $this->assertEquals([$firstResolver, $secondResolver, $thirdResolver], $resolvers);

    $result = $this->chainCountryResolver->resolve();
    $this->assertEquals('RS', $result);
  }

}

