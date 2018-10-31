<?php

namespace Drupal\Tests\commerce_payment\Unit;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\CreditCardType;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_payment\CreditCard
 * @group commerce
 */
class CreditCardTest extends UnitTestCase {

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
   * @covers ::validateNumber
   * @covers ::validateLuhn
   * @dataProvider cardsProvider
   */
  public function testValidateNumber($number, $type, $valid) {
    $detected_type = CreditCard::detectType($number);
    if ($detected_type) {
      $this->assertEquals($detected_type->getId(), $type);
      $result = CreditCard::validateNumber($number, $detected_type);
      $this->assertEquals($valid, $result);
    }
    else {
      $this->assertEquals(NULL, $type);
    }
  }

  /**
   * @covers ::validateExpirationDate
   * @dataProvider expirationDateProvider
   */
  public function testValidateExpirationDate($month, $year, $valid) {
    $result = CreditCard::validateExpirationDate($month, $year);
    $this->assertEquals($valid, $result);
  }

  /**
   * @covers ::calculateExpirationTimestamp
   */
  public function testCalculateExpirationTimestamp() {
    $timestamp = CreditCard::calculateExpirationTimestamp(12, 2012);
    $date = date('Y-m-d H:i:s', $timestamp);
    $expected_date = date('2012-12-31 23:59:59');
    $this->assertEquals($expected_date, $date);
  }

  /**
   * @covers ::validateSecurityCode
   * @dataProvider securityCodeProvider
   */
  public function testsValidateSecurityCode($security_code, $type, $valid) {
    $type = CreditCard::getType($type);
    $result = CreditCard::validateSecurityCode($security_code, $type);
    $this->assertEquals($valid, $result);
  }

  /**
   * Data provider for ::testValidateNumber.
   *
   * @return array
   *   A list of testValidateNumber function arguments.
   */
  public function cardsProvider() {
    return [
      // Non-numeric value.
      ['invalid', NULL, FALSE],
      // Invalid length.
      [41111111111111111, 'visa', FALSE],
      // Fails luhn check.
      [41111111111111112, 'visa', FALSE],
      // Valid numbers.
      [4111111111111111, 'visa', TRUE],
      [6759649826438453, 'maestro', TRUE],
      [3528000700000000, 'jcb', TRUE],
      [5555555555554444, 'mastercard', TRUE],
      [36700102000000, 'dinersclub', TRUE],
      [6011000400000000, 'discover', TRUE],
      [6208205838887174, 'unionpay', TRUE],
      [374251018720018, 'amex', TRUE],
    ];
  }

  /**
   * Data provider for ::testValidateExpirationDate.
   *
   * @return array
   *   A list of testValidateExpirationDate function arguments.
   */
  public function expirationDateProvider() {
    return [
      // Invalid month.
      [0, 2020, FALSE],
      [13, 2020, FALSE],
      // Invalid year.
      [10, 2012, FALSE],
      // Valid month and year.
      [date('n'), date('Y'), TRUE],
    ];
  }

  /**
   * Data provider for ::testValidateSecurityCode.
   *
   * @return array
   *   A list of testValidateSecurityCode function arguments.
   */
  public function securityCodeProvider() {
    return [
      // Invalid lengths.
      [1, 'visa', FALSE],
      [1111, 'visa', FALSE],
      // Non-numeric inputs.
      ['llama', 'visa', FALSE],
      ['12.4', 'visa', FALSE],
      // Valid number.
      [111, 'visa', TRUE],
    ];
  }

}
