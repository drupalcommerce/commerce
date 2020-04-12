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
    $this->expectException('InvalidArgumentException');
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

    $theme = [
      '#theme' => 'commerce_price_test',
      '#price' => new Price('20', 'USD'),
      '#options' => [
        'currency_display' => 'code',
        'minimum_fraction_digits' => 0,
      ],
    ];
    $this->render($theme);
    $this->assertNoText('USD20.00');
    $this->assertText('USD20');
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
