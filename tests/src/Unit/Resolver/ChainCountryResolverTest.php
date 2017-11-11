<?php

namespace Drupal\Tests\commerce\Unit\Resolver;

use Drupal\commerce\Resolver\ChainCountryResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass  Drupal\commerce\Resolver\ChainCountryResolver
 * @group commerce
 */
class ChainCountryResolverTest extends UnitTestCase {

  /**
   * The chain country resolver.
   *
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
   * Tests the resolver and priority.
   *
   * ::covers addResolver
   * ::covers getResolvers
   * ::covers resolve.
   */
  public function testResolver() {
    $container = new ContainerBuilder();

    $mock_builder = $this->getMockBuilder('Drupal\commerce\Resolver\CountryResolverInterface')
      ->disableOriginalConstructor();

    $first_resolver = $mock_builder->getMock();
    $first_resolver->expects($this->once())
      ->method('resolve');
    $container->set('commerce.first_resolver', $first_resolver);

    $second_resolver = $mock_builder->getMock();
    $second_resolver->expects($this->once())
      ->method('resolve')
      ->willReturn('RS');
    $container->set('commerce.second_resolver', $second_resolver);

    $third_resolver = $mock_builder->getMock();
    $third_resolver->expects($this->never())
      ->method('resolve');
    $container->set('commerce.third_resolver', $third_resolver);

    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $resolvers = [
      'commerce.first_resolver' => 900,
      'commerce.second_resolver' => 400,
      'commerce.third_resolver' => -100,
    ];
    arsort($resolvers, SORT_NUMERIC);
    foreach ($resolvers as $id => $priority) {
      $this->chainCountryResolver->addResolver($container->get($id));
    }

    $result = $this->chainCountryResolver->resolve();
    $this->assertEquals('RS', $result);
  }

}
