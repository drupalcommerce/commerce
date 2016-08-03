<?php

namespace Drupal\Tests\commerce_payment\Unit;

use Drupal\commerce_payment\CreditCardType;

/**
 * Unit test for CreditCardType.
 * @coversDefaultClass \Drupal\commerce_payment\CreditCardType
 */
class CreditCardTypeTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests creation of class.
   *
   * @dataProvider definitionProvider
   */
  public function testClassCreation($definition, $message) {
    if ($message) {
      $this->setExpectedException(\InvalidArgumentException::class, $message);
    }
    $card = new CreditCardType($definition);
    $this->assertInstanceof(CreditCardType::class, $card);
  }

  /**
   * Data provider for ::testClassCreation.
   *
   * @return array
   */
  public function definitionProvider() {
    return [
      [[], 'Missing required property id.'],
      [['id' => 'llama'], 'Missing required property label.'],
      [
        [
          'id' => 'llama',
          'label' => 'Llama',
        ],
        'Missing required property number_prefixes.'
      ],
      [
        [
          'id' => 'llama',
          'label' => 'Llama',
          'number_prefixes' => ['4'],
        ],
        FALSE,
      ],
    ];
  }

}
