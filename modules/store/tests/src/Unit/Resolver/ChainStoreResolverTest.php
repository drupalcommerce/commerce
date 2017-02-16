<?php

namespace Drupal\Tests\commerce_store\Unit\Resolver;

use Drupal\commerce_store\Resolver\ChainStoreResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
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
   * Tests the resolver and priority.
   *
   * ::covers addResolver
   * ::covers getResolvers
   * ::covers resolve.
   */
  public function testResolver() {
    $container = new ContainerBuilder();

    $mock_builder = $this->getMockBuilder('Drupal\commerce_store\Resolver\StoreResolverInterface')
      ->disableOriginalConstructor();

    $first_resolver = $mock_builder->getMock();
    $first_resolver->expects($this->once())
      ->method('resolve');
    $container->set('commerce.first_resolver', $first_resolver);

    $second_resolver = $mock_builder->getMock();
    $second_resolver->expects($this->once())
      ->method('resolve')
      ->willReturn('testStore');
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
      $this->resolver->addResolver($container->get($id));
    }

    $result = $this->resolver->resolve();
    $this->assertEquals('testStore', $result);
  }

}
