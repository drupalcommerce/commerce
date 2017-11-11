<?php

namespace Drupal\Tests\commerce\Unit\Resolver;

use Drupal\commerce\Resolver\DefaultLocaleResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\Resolver\DefaultLocaleResolver
 * @group commerce
 */
class DefaultLocaleResolverTest extends UnitTestCase {

  /**
   * @covers ::resolve
   */
  public function testLanguageCountry() {
    $language = $this->getMockBuilder('\Drupal\Core\Language\Language')
      ->disableOriginalConstructor()
      ->getMock();
    $language->expects($this->once())
      ->method('getId')
      ->will($this->returnValue('sr'));

    $language_manager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->once())
      ->method('getConfigOverrideLanguage')
      ->will($this->returnValue($language));

    $country_context = $this->getMockBuilder('\Drupal\commerce\CurrentCountry')
      ->disableOriginalConstructor()
      ->getMock();
    $country_context->expects($this->once())
      ->method('getCountry')
      ->will($this->returnValue('RS'));

    $resolver = new DefaultLocaleResolver($language_manager, $country_context);
    $this->assertEquals('sr-RS', $resolver->resolve());
  }

  /**
   * @covers ::resolve
   */
  public function testLanguageWithCountryComponent() {
    $language = $this->getMockBuilder('\Drupal\Core\Language\Language')
      ->disableOriginalConstructor()
      ->getMock();
    $language->expects($this->once())
      ->method('getId')
      ->will($this->returnValue('pt-br'));

    $language_manager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->once())
      ->method('getConfigOverrideLanguage')
      ->will($this->returnValue($language));

    $country_context = $this->getMockBuilder('\Drupal\commerce\CurrentCountry')
      ->disableOriginalConstructor()
      ->getMock();

    $resolver = new DefaultLocaleResolver($language_manager, $country_context);
    $this->assertEquals('pt-br', $resolver->resolve());
  }

  /**
   * @covers ::resolve
   */
  public function testUnknownCountry() {
    $language = $this->getMockBuilder('\Drupal\Core\Language\Language')
      ->disableOriginalConstructor()
      ->getMock();
    $language->expects($this->once())
      ->method('getId')
      ->will($this->returnValue('sr'));

    $language_manager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->once())
      ->method('getConfigOverrideLanguage')
      ->will($this->returnValue($language));

    $country_context = $this->getMockBuilder('\Drupal\commerce\CurrentCountry')
      ->disableOriginalConstructor()
      ->getMock();
    $country_context->expects($this->once())
      ->method('getCountry')
      ->will($this->returnValue(NULL));

    $resolver = new DefaultLocaleResolver($language_manager, $country_context);
    $this->assertEquals('sr', $resolver->resolve());
  }

}
