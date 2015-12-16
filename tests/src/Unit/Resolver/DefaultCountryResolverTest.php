<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce\Unit\Resolver\DefaultCountryResolverTest.
 */

namespace Drupal\Tests\commerce\Unit\Resolver;

use Drupal\commerce\Resolver\DefaultCountryResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\Resolver\DefaultCountryResolver
 * @group commerce
 */
class DefaultCountryResolverTest extends UnitTestCase {

  /**
   * The resolver.
   *
   * @var \Drupal\commerce\Resolver\DefaultCountryResolver
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
      ->with('country.default')
      ->will($this->returnValue('RS'));

    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('get')
      ->with('system.date')
      ->will($this->returnValue($config));

    $this->resolver = new DefaultCountryResolver($config_factory);
  }

  /**
   * @covers ::resolve
   */
  public function testResolve() {
    $countryCode = $this->resolver->resolve();
    $this->assertEquals('RS', $countryCode);
  }

}
