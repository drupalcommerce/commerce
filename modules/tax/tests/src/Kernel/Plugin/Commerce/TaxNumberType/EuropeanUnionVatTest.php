<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxNumberType;

use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\EuropeanUnionVat;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\EuropeanUnionVat
 * @group commerce
 */
class EuropeanUnionVatTest extends OrderKernelTestBase {

  /**
   * The tax number type plugin.
   *
   * @var \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\EuropeanUnionVat
   */
  protected $plugin;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $plugin_manager = $this->container->get('plugin.manager.commerce_tax_number_type');
    $this->plugin = $plugin_manager->createInstance('european_union_vat');
  }

  /**
   * @covers ::getLabel
   * @covers ::getCountries
   * @covers ::getExamples
   * @covers ::getFormattedExamples
   */
  public function testGetters() {
    $plugin_definition = $this->plugin->getPluginDefinition();
    $this->assertEquals($plugin_definition['label'], $this->plugin->getLabel());
    $this->assertEquals($plugin_definition['countries'], $this->plugin->getCountries());
    $this->assertEquals(['DE123456789', 'HU12345678'], $this->plugin->getExamples());
    $this->assertEquals('Examples: DE123456789, HU12345678.', $this->plugin->getFormattedExamples());
  }

  /**
   * @covers ::canonicalize
   */
  public function testCanonicalize() {
    // Confirm that spaces, dots, and dashes are removed by default.
    $this->assertEquals('DE123456789', $this->plugin->canonicalize('DE 123456789'));
    $this->assertEquals('FR00123456789', $this->plugin->canonicalize('FR 00.123-456-789'));
  }

  /**
   * @covers ::validate
   */
  public function testValidate() {
    $valid_numbers = [
      'ATU13585626', 'BE0428759497', 'BG175074752', 'BG7523169263',
      'CY10259033P', 'CZ25123891', 'CZ991231123', 'CZ7103192745',
      'DE136695978', 'DK13585627', 'EE100594102', 'EL094259216',
      'ES54362315K', 'ESX2482300W', 'ESB58378431', 'FI20774740',
      'FR40303265045', 'FRK7399859412', 'GB802311781', 'GB123123412123',
      'GBGD001', 'GBHA500', 'HR33392005962', 'HU12892312', 'IE6433435F',
      'IE6433435OA', 'IT00743110157', 'LT119511515', 'LT100001919017',
      'LU15027442', 'LV16137519997', 'MT11679112', 'NL123456789B90',
      'PL8567346215', 'PT501964842', 'RO18547291', 'SE123456789101',
      'SI50223054', 'SK2022749618',
    ];
    foreach ($valid_numbers as $number) {
      $this->assertTrue($this->plugin->validate($number), $number);
    }

    $invalid_numbers = [
      'AT13585626', 'ATX13585626', 'BE0428', 'DEABCDEFGHI', 'DK135856279',
    ];
    foreach ($invalid_numbers as $number) {
      $this->assertFalse($this->plugin->validate($number), $number);
    }

    // Confirm that a valid number without a prefix is not accepted.
    $this->assertFalse($this->plugin->validate('U13585626'));

    // Confirm that numbers are treated as case sensitive.
    $this->assertFalse($this->plugin->validate('atU13585626'));
    $this->assertFalse($this->plugin->validate('ATu13585626'));
  }

  /**
   * @covers ::doVerify
   */
  public function testVerify() {
    $request_time = $this->container->get('datetime.time')->getRequestTime();
    $wsdl = drupal_get_path('module', 'commerce_tax') . '/tests/fixtures/checkVatService.wsdl';
    $soap_client = $this->getMockFromWsdl($wsdl);
    // Modify the plugin's protected property to use the mock.
    $property = new \ReflectionProperty(EuropeanUnionVat::class, 'soapClient');
    $property->setAccessible(TRUE);
    $property->setValue($this->plugin, $soap_client);

    $invalid_result = new \stdClass();
    $invalid_result->valid = FALSE;
    $invalid_result->name = '---';
    $invalid_result->address = '---';

    $valid_result = new \stdClass();
    $valid_result->valid = TRUE;
    $valid_result->name = 'SLRL ALTEA EXPERTISE COMPTABLE';
    $valid_result->address = '59 RUE DU MURIER';

    $parameters = [
      'countryCode' => 'AT',
      'vatNumber' => 'U13585626',
    ];
    $soap_client->expects($this->at(0))
      ->method('__soapCall')
      ->with('checkVat', [$parameters])
      ->will($this->returnValue($invalid_result));

    $parameters = [
      'countryCode' => 'FR',
      'vatNumber' => 'K7399859412',
    ];
    $soap_client->expects($this->at(1))
      ->method('__soapCall')
      ->with('checkVat', [$parameters])
      ->will($this->returnValue($valid_result));

    $result = $this->plugin->verify('123456');
    $this->assertTrue($result->isFailure());
    $this->assertEquals($request_time, $result->getTimestamp());
    $this->assertEquals(['error' => 'invalid_number'], $result->getData());

    $result = $this->plugin->verify('ATU13585626');
    $this->assertTrue($result->isFailure());
    $this->assertEquals($request_time, $result->getTimestamp());
    $this->assertEquals([], $result->getData());

    $result = $this->plugin->verify('FRK7399859412');
    $this->assertTrue($result->isSuccess());
    $this->assertEquals($request_time, $result->getTimestamp());
    $this->assertEquals([
      'name' => 'SLRL ALTEA EXPERTISE COMPTABLE',
      'address' => '59 RUE DU MURIER',
    ], $result->getData());
  }

  /**
   * @covers ::renderVerificationResult
   */
  public function testRenderVerificationResult() {
    $request_time = $this->container->get('datetime.time')->getRequestTime();

    // Pre-defined error.
    $result = VerificationResult::failure($request_time, ['error' => 'invalid_number']);
    $element = $this->plugin->renderVerificationResult($result);
    $this->assertArrayHasKey('error', $element);
    $this->assertArrayNotHasKey('name', $element);
    $this->assertArrayNotHasKey('address', $element);
    $this->assertEquals('The tax number is not in the right format.', (string) $element['error']['#plain_text']);

    // Unknown error.
    $result = VerificationResult::failure($request_time, ['error' => 'An unknown error occurred.']);
    $element = $this->plugin->renderVerificationResult($result);
    $this->assertArrayHasKey('error', $element);
    $this->assertArrayNotHasKey('name', $element);
    $this->assertArrayNotHasKey('address', $element);
    $this->assertEquals('An unknown error occurred.', $element['error']['#plain_text']);

    // Name and address.
    $result = VerificationResult::success($request_time, [
      'name' => 'John Smith',
      'address' => '9 Drupal Ave',
    ]);
    $element = $this->plugin->renderVerificationResult($result);
    $this->assertArrayNotHasKey('error', $element);
    $this->assertArrayHasKey('name', $element);
    $this->assertArrayHasKey('address', $element);
    $this->assertEquals('John Smith', $element['name']['#plain_text']);
    $this->assertEquals('9 Drupal Ave', $element['address']['#plain_text']);
  }

}
