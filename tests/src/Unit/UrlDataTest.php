<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\UrlData;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\UrlData
 * @group commerce
 */
class UrlDataTest extends UnitTestCase {

  /**
   * ::covers encode
   * ::covers decode.
   */
  public function testEncodeDecode() {
    $data = ['commerce_product', '1'];
    $encoded_data = UrlData::encode($data);
    $this->assertInternalType('string', $encoded_data);

    $decoded_data = UrlData::decode($encoded_data);
    $this->assertInternalType('array', $decoded_data);
    $this->assertSame($data, $decoded_data);

    $invalid_data = UrlData::decode('INVALID');
    $this->assertFalse($invalid_data);
  }

}
