<?php

namespace Drupal\Tests\commerce_payment\Unit;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\CreditCardType;

/**
 * Unit test for CreditCard.
 *
 * @coversDefaultClass \Drupal\commerce_payment\CreditCard
 */
class CreditCardTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::getTypes
   */
  public function testGetTypes() {
    $types = CreditCard::getTypes();
    $this->assertInternalType('array', $types);
    foreach ($types as $key => $type) {
      $this->assertInstanceOf(CreditCardType::class, $type);
      $this->assertEquals($key, $type->getId());
    }
  }

  /**
   * @covers ::getType
   */
  public function testGetInvalidType() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid credit card type "monkey"');
    CreditCard::getType("monkey");
  }

  /**
   * @covers ::getType
   */
  public function testGetType() {
    $visa = CreditCard::getType('visa');
    $this->assertInstanceOf(CreditCardType::class, $visa);
    $this->assertEquals('visa', $visa->getId());
  }

  /**
   * @covers ::detectType
   * @covers ::matchPrefix
   * @dataProvider cardsProvider
   */
  public function testDetectType($number, $type) {
    $card = CreditCard::detectType($number);
    $this->assertInstanceOf(CreditCardType::class, $card);
    $this->assertEquals($type, $card->getId());
  }

  /**
   * @covers ::detectType
   */
  public function testInvalidType() {
    $card = CreditCard::detectType('4111 1111 1111 1111');
    $this->assertFalse($card);
  }

  /**
   * @covers ::validateNumber
   * @covers ::getType
   * @covers ::validateLuhn
   * @dataProvider cardsProvider
   */
  public function testValidateNumber($number, $type) {
    $creditCardType = CreditCard::getType($type);
    $validated = CreditCard::validateNumber($number, $creditCardType);
    $this->assertTrue($validated);
  }

  /**
   * @covers ::detectType
   * @covers ::matchPrefix
   * @dataProvider invalidCardsProvider
   */
  public function testInvalidNumbers($number) {
    $card = CreditCard::detectType($number);
    $validated = CreditCard::validateNumber($number, $card);
    $this->assertFalse($validated);
  }

  /**
   * @covers ::validateSecurityCode
   */
  public function testValidateSecurityCode() {
    // Check length.
    $validated = CreditCard::validateSecurityCode(1, CreditCard::getType('visa'));
    $this->assertFalse($validated);
    $validated = CreditCard::validateSecurityCode(1111, CreditCard::getType('visa'));
    $this->assertFalse($validated);

    // Check that security code is a number.
    $validated = CreditCard::validateSecurityCode("llama", CreditCard::getType('visa'));
    $this->assertFalse($validated);
    $validated = CreditCard::validateSecurityCode("12.4", CreditCard::getType('visa'));
    $this->assertFalse($validated);

    // Valid security code.
    $validated = CreditCard::validateSecurityCode(111, CreditCard::getType('visa'));
    $this->assertTrue($validated);
  }

  /**
   * Data provider for ::testDetectType.
   *
   * @return array
   *   An array of credit cards numbers and the expected type.
   */
  public function cardsProvider() {
    return [
      [4111111111111111, 'visa'],
      [6759649826438453, 'maestro'],
      [3528000700000000, 'jcb'],
      [5555555555554444, 'mastercard'],
      [36700102000000, 'dinersclub'],
      [6011000400000000, 'discover'],
      [6208205838887174, 'unionpay'],
      [374251018720018, 'amex'],
    ];
  }

  /**
   * Data provider for ::testInvalidTypes.
   *
   * @return array
   *   An array of invalid credit cards numbers.
   */
  public function invalidCardsProvider() {
    return [
      [41111111111111],
      [3742510187200181212],
      [37425101872],
      [37],
    ];
  }

}
