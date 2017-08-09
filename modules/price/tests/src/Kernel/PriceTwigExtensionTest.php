<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Price;
use Drupal\Core\Site\Settings;
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
   * Tests an improperly formatted price array.
   */
  public function testBrokenPrice() {
    $theme = ['#theme' => 'broken_commerce_price'];
    $this->setExpectedException('InvalidArgumentException');
    $this->render($theme);
  }

  /**
   * Tests a properly formatted price array.
   */
  public function testWorkingPrice() {
    $theme = ['#theme' => 'working_commerce_price'];
    $this->render($theme);
    $this->assertText('$9.99');
  }

  /**
   * Tests a price object.
   */
  public function testPriceObject() {
    $theme = ['#theme' => 'commerce_price_object'];
    $this->render($theme);
    $this->assertText('$9.99');
  }

  /**
   * Tests that a price object is rounded by filter.
   */
  public function testPriceRounding() {
    $settings = Settings::getAll();
    $settings['twig_sandbox_whitelisted_methods'] = [
      // Only allow idempotent methods.
      'id', 'label', 'bundle', 'get', '__toString', 'toString',
      'divide',
      'multiply',
    ];
    new Settings($settings);

    $price = new Price('199.90', 'USD');
    $theme = [
      '#type' => 'inline_template',
      '#template' => "{{ price.divide('12')|commerce_price_format }}",
      '#context' => ['price' => $price],
    ];
    $this->render($theme);
    $this->assertText('$16.66');

    $theme = [
      '#type' => 'inline_template',
      '#template' => "{{ price|commerce_price_format }}",
      '#context' => [
        'price' => [
          'number' => '9.998',
          'currency_code' => 'USD',
        ],
      ],
    ];
    $this->render($theme);
    $this->assertText('$10.00');
  }

}
