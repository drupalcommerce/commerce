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
    $mockBuilder = $this->getMockBuilder('Drupal\commerce\Resolver\LocaleResolverInterface')
      ->disableOriginalConstructor();

    $firstResolver = $mockBuilder->getMock();
    $firstResolver->expects($this->once())
      ->method('resolve');

    $secondResolver = $mockBuilder->getMock();
    $secondResolver->expects($this->once())
      ->method('resolve')
      ->willReturn('mk_MK');

    $thirdResolver = $mockBuilder->getMock();
    $thirdResolver->expects($this->never())
      ->method('resolve');

    $this->chainLocaleResolver->addResolver($firstResolver);
    $this->chainLocaleResolver->addResolver($secondResolver);
    $this->chainLocaleResolver->addResolver($thirdResolver);

    $resolvers = $this->chainLocaleResolver->getResolvers();
    $this->assertEquals([$firstResolver, $secondResolver, $thirdResolver], $resolvers);

    $result = $this->chainLocaleResolver->resolve();
    $this->assertEquals('mk_MK', $result);
  }

}
