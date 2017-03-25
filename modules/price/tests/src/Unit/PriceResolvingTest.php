<?php

namespace Drupal\Tests\commerce_price\Unit;

use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\ChainPriceResolver;
use Drupal\commerce_price\Resolver\DefaultPriceResolver;
use Drupal\commerce_price_test\TestPriceResolver;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Tests price resolving.
 *
 * @group commerce
 */
class PriceResolvingTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'commerce',
    'commerce_price',
    'commerce_price_test',
  ];

  /**
   * Tests price revolving.
   *
   * ::covers addResolver
   * ::covers getResolvers
   * ::covers resolve.
   */
  public function testResolver() {
    $container = new ContainerBuilder();
    $chain_resolver = new ChainPriceResolver();
    $container->set('commerce_price.default_price_resolver', new DefaultPriceResolver());
    $container->set('commerce_price.test_price_resolver', new TestPriceResolver());
    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $resolvers = [
      'commerce_price.default_price_resolver' => -100,
      'commerce_price.test_price_resolver' => 100,
    ];
    arsort($resolvers, SORT_NUMERIC);
    foreach ($resolvers as $id => $priority) {
      $chain_resolver->addResolver($container->get($id));
    }

    $mock_user = $this->getMock(UserInterface::class);
    $mock_store = $this->getMock(StoreInterface::class);
    $mock_context = new Context($mock_user, $mock_store);

    $mock_variant = $this->getMock(ProductVariationInterface::class);
    $mock_variant->expects($this->once())->method('getSku')->willReturn('TEST_MOCK');
    $mock_variant->expects($this->exactly(2))->method('getPrice')->willReturn(new Price('20.00', 'USD'));
    $resolved_price = $chain_resolver->resolve($mock_variant, 1, $mock_context);
    $this->assertEquals($resolved_price, new Price('17.00', 'USD'));

    $mock_variant = $this->getMock(ProductVariationInterface::class);
    $mock_variant->expects($this->once())->method('getSku')->willReturn('MOCK');
    $mock_variant->expects($this->exactly(1))->method('getPrice')->willReturn(new Price('20.00', 'USD'));
    $resolved_price = $chain_resolver->resolve($mock_variant, 1, $mock_context);
    $this->assertEquals($resolved_price, new Price('20.00', 'USD'));
  }

}
