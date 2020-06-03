<?php declare(strict_types = 1);

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\commerce_price\Price;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests generating sample values for price fields.
 *
 * @group commerce
 */
class PriceItemGeneratedSampleValueTest extends CommerceKernelTestBase {

  /**
   * Tests the generated sample value.
   *
   * @dataProvider dataForGeneratedSamples
   */
  public function testGeneratedSampleValue(array $available_currencies) {
    $currency_importer = $this->container->get('commerce_price.currency_importer');
    foreach ($available_currencies as $available_currency) {
      $currency_importer->import($available_currency);
    }
    $definition = BaseFieldDefinition::create('commerce_price')
      ->setSetting('available_currencies', $available_currencies);
    $sample = PriceItem::generateSampleValue($definition);
    $this->assertIsArray($sample);
    $price = Price::fromArray($sample);

    $currency_code = reset($available_currencies);
    $this->assertEquals($currency_code, $price->getCurrencyCode());
  }

  /**
   * Test data for generated samples.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataForGeneratedSamples() {
    yield [
      ['USD'],
    ];
    yield [
      ['CAD'],
    ];
    yield [
      ['EUR', 'USD'],
    ];
  }

}
