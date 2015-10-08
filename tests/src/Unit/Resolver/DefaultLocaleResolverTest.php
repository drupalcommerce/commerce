<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce\Unit\Resolver\DefaultLocaleResolverTest.
 */

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

    $languageManager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $languageManager->expects($this->once())
      ->method('getConfigOverrideLanguage')
      ->will($this->returnValue($language));

    $countryContext = $this->getMockBuilder('\Drupal\commerce\CountryContext')
      ->disableOriginalConstructor()
      ->getMock();
    $countryContext->expects($this->once())
      ->method('getCountry')
      ->will($this->returnValue('RS'));

    $resolver = new DefaultLocaleResolver($languageManager, $countryContext);
    $this->assertEquals('SR-RS', $resolver->resolve());
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
      ->will($this->returnValue('PT-BR'));

    $languageManager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $languageManager->expects($this->once())
      ->method('getConfigOverrideLanguage')
      ->will($this->returnValue($language));

    $countryContext = $this->getMockBuilder('\Drupal\commerce\CountryContext')
      ->disableOriginalConstructor()
      ->getMock();

    $resolver = new DefaultLocaleResolver($languageManager, $countryContext);
    $this->assertEquals('PT-BR', $resolver->resolve());
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
      ->will($this->returnValue('SR'));

    $languageManager = $this->getMockBuilder('\Drupal\Core\Language\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $languageManager->expects($this->once())
      ->method('getConfigOverrideLanguage')
      ->will($this->returnValue($language));

    $countryContext = $this->getMockBuilder('\Drupal\commerce\CountryContext')
      ->disableOriginalConstructor()
      ->getMock();
    $countryContext->expects($this->once())
      ->method('getCountry')
      ->will($this->returnValue(NULL));

    $resolver = new DefaultLocaleResolver($languageManager, $countryContext);
    $this->assertEquals('SR', $resolver->resolve());
  }

}
