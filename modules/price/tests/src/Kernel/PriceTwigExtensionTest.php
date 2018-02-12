<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the price twig filter.
 *
 * @group commerce
 */
class PriceTwigExtensionTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price_test',
  ];

  /**
   * Tests passing an invalid value.
   */
  public function testInvalidPrice() {
    $theme = [
      '#theme' => 'commerce_price_test',
      '#price' => [
        // Invalid keys.
        'numb' => '9.99',
        'currency_co' => 'USD',
      ],
    ];
    $this->setExpectedException('InvalidArgumentException');
    $this->render($theme);
  }

  /**
   * Tests passing a valid value.
   */
  public function testValidPrice() {
    $theme = [
      '#theme' => 'commerce_price_test',
      '#price' => [
        'number' => '9.99',
        'currency_code' => 'USD',
      ],
    ];
    $this->render($theme);
    $this->assertText('$9.99');

    $theme = [
      '#theme' => 'commerce_price_test',
      '#price' => new Price('20.99', 'USD'),
    ];
    $this->render($theme);
    $this->assertText('$20.99');
  }

  /**
   * Tests passing an empty value.
   */
  public function testEmptyPrice() {
    $theme = ['#theme' => 'commerce_price_test'];
    $this->render($theme);
    $this->assertText('N/A');
  }

}
