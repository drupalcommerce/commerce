<?php

namespace Drupal\commerce_payment;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides logic for listing card types and validating card details.
 */
final class CreditCard {

  /**
   * The instantiated credit card types.
   *
   * @var \Drupal\commerce_payment\CreditCardType[]
   */
  public static $types = [];

  /**
   * Gets the credit card type with the given ID.
   *
   * @param string $id
   *   The credit card type ID. For example: 'visa'.
   *
   * @return \Drupal\commerce_payment\CreditCardType
   *   The credit card type.
   */
  public static function getType(string $id) : CreditCardType {
    $types = self::getTypes();
    if (!isset($types[$id])) {
      throw new \InvalidArgumentException(sprintf('Invalid credit card type "%s"', $id));
    }

    return $types[$id];
  }

  /**
   * Gets all available credit card types.
   *
   * @return \Drupal\commerce_payment\CreditCardType[]
   *   The credit card types.
   */
  public static function getTypes() : array {
    $definitions = [
      'visa' => [
        'id' => 'visa',
        'label' => new TranslatableMarkup('Visa'),
        'number_prefixes' => ['4'],
        'number_lengths' => [16, 18, 19],
      ],
      'mastercard' => [
        'id' => 'mastercard',
        'label' => new TranslatableMarkup('Mastercard'),
        'number_prefixes' => ['51-55', '222100-272099'],
      ],
      'maestro' => [
        'id' => 'maestro',
        'label' => new TranslatableMarkup('Maestro'),
        'number_prefixes' => [
          '5018', '502', '503', '506', '56', '58', '639', '6220', '67',
        ],
        'number_lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
      ],
      'amex' => [
        'id' => 'amex',
        'label' => new TranslatableMarkup('American Express'),
        'number_prefixes' => ['34', '37'],
        'number_lengths' => [15],
        'security_code_length' => 4,
      ],
      'dinersclub' => [
        'id' => 'dinersclub',
        'label' => new TranslatableMarkup('Diners Club'),
        'number_prefixes' => ['300-305', '309', '36', '38', '39'],
        'number_lengths' => [14, 16, 19],
      ],
      'discover' => [
        'id' => 'discover',
        'label' => new TranslatableMarkup('Discover Card'),
        'number_prefixes' => ['6011', '622126-622925', '644-649', '65'],
        'number_lengths' => [16, 19],
      ],
      'jcb' => [
        'id' => 'jcb',
        'label' => new TranslatableMarkup('JCB'),
        'number_prefixes' => ['3528-3589'],
        'number_lengths' => [16, 17, 18, 19],
      ],
      'unionpay' => [
        'id' => 'unionpay',
        'label' => new TranslatableMarkup('UnionPay'),
        'number_prefixes' => ['62', '88'],
        'number_lengths' => [16, 17, 18, 19],
        'uses_luhn' => FALSE,
      ],
    ];
    foreach ($definitions as $id => $definition) {
      self::$types[$id] = new CreditCardType($definition);
    }

    return self::$types;
  }

  /**
   * Gets the labels of all available credit card types.
   *
   * @return array
   *   The labels, keyed by ID.
   */
  public static function getTypeLabels() : array {
    $types = self::getTypes();
    $type_labels = array_map(function ($type) {
      return $type->getLabel();
    }, $types);

    return $type_labels;
  }

  /**
   * Detects the credit card type based on the number.
   *
   * @param string $number
   *   The credit card number.
   *
   * @return \Drupal\commerce_payment\CreditCardType|null
   *   The credit card type, or NULL if unknown.
   */
  public static function detectType($number) {
    if (!is_numeric($number)) {
      return NULL;
    }
    $types = self::getTypes();
    foreach ($types as $type) {
      foreach ($type->getNumberPrefixes() as $prefix) {
        if (self::matchPrefix($number, $prefix)) {
          return $type;
        }
      }
    }

    return NULL;
  }

  /**
   * Checks whether the given credit card number matches the given prefix.
   *
   * @param string $number
   *   The credit card number.
   * @param string $prefix
   *   The prefix to match against. Can be a single number such as '43' or a
   *   range such as '30-35'.
   *
   * @return bool
   *   TRUE if the credit card number matches the prefix, FALSE otherwise.
   */
  public static function matchPrefix(string $number, string $prefix) : bool {
    if (is_numeric($prefix)) {
      return substr($number, 0, strlen($prefix)) == $prefix;
    }
    else {
      list($start, $end) = explode('-', $prefix);
      $number = substr($number, 0, strlen($start));
      return $number >= $start && $number <= $end;
    }
  }

  /**
   * Validates the given credit card number.
   *
   * @param string $number
   *   The credit card number.
   * @param \Drupal\commerce_payment\CreditCardType $type
   *   The credit card type.
   *
   * @return bool
   *   TRUE if the credit card number is valid, FALSE otherwise.
   */
  public static function validateNumber(string $number, CreditCardType $type) : bool {
    if (!is_numeric($number)) {
      return FALSE;
    }
    if (!in_array(strlen($number), $type->getNumberLengths())) {
      return FALSE;
    }
    if ($type->usesLuhn() && !self::validateLuhn($number)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates the given credit card number using the Luhn algorithm.
   *
   * @param string $number
   *   The credit card number.
   *
   * @return bool
   *   TRUE if the credit card number is valid, FALSE otherwise.
   */
  public static function validateLuhn(string $number) : bool {
    $total = 0;
    foreach (array_reverse(str_split($number)) as $i => $digit) {
      $digit = $i % 2 ? $digit * 2 : $digit;
      $digit = $digit > 9 ? $digit - 9 : $digit;
      $total += $digit;
    }
    return ($total % 10 === 0);
  }

  /**
   * Validates the given credit card expiration date.
   *
   * @param string $month
   *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
   * @param string $year
   *   The 4-digit numeric representation of the year, i.e. 2010.
   *
   * @return bool
   *   TRUE if the credit card expiration date is valid, FALSE otherwise.
   */
  public static function validateExpirationDate(string $month, string $year) : bool {
    if ($month < 1 || $month > 12) {
      return FALSE;
    }
    if ($year < date('Y')) {
      return FALSE;
    }
    elseif ($year == date('Y') && $month < date('n')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Calculates the unix timestamp for a credit card expiration date.
   *
   * @param string $month
   *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
   * @param string $year
   *   The 4-digit numeric representation of the year, i.e. 2010.
   *
   * @return int
   *   The expiration date as a unix timestamp.
   */
  public static function calculateExpirationTimestamp(string $month, string $year) : int {
    // Credit cards expire on the last day of the month.
    $month_start = strtotime($year . '-' . $month . '-01');
    $last_day = date('t', $month_start);
    return mktime(23, 59, 59, $month, $last_day, $year);
  }

  /**
   * Validates the given credit card security code.
   *
   * @param string $security_code
   *   The credit card security code.
   * @param \Drupal\commerce_payment\CreditCardType $type
   *   The credit card type.
   *
   * @return bool
   *   TRUE if the credit card security code is valid, FALSE otherwise.
   */
  public static function validateSecurityCode(string $security_code, CreditCardType $type) : bool {
    if (!is_numeric($security_code)) {
      return FALSE;
    }
    if (strlen($security_code) != $type->getSecurityCodeLength()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets all available AVS response code meanings.
   *
   * @return array
   *   The AVS response code meanings.
   *
   * @see https://developer.paypal.com/docs/nvp-soap-api/AVSResponseCodes/#avs-error-response-codes
   */
  public static function getAvsResponseCodeMeanings() : array {
    // AVS codes for Visa, MasterCard, Discover, and American Express.
    $standard_codes = [
      'A' => new TranslatableMarkup('Address'),
      'B' => new TranslatableMarkup('International "A"'),
      'C' => new TranslatableMarkup('International "N"'),
      'D' => new TranslatableMarkup('International "X"'),
      'E' => new TranslatableMarkup('Not allowed for MOTO (Internet/Phone) transactions'),
      'F' => new TranslatableMarkup('UK-specific "X"'),
      'G' => new TranslatableMarkup('Global Unavailable'),
      'I' => new TranslatableMarkup('International Unavailable'),
      'M' => new TranslatableMarkup('Address'),
      'N' => new TranslatableMarkup('No'),
      'P' => new TranslatableMarkup('Postal (International "Z")'),
      'R' => new TranslatableMarkup('Retry'),
      'S' => new TranslatableMarkup('AVS not Supported'),
      'U' => new TranslatableMarkup('Unavailable'),
      'W' => new TranslatableMarkup('Whole ZIP'),
      'X' => new TranslatableMarkup('Exact match'),
      'Y' => new TranslatableMarkup('Yes'),
      'Z' => new TranslatableMarkup('ZIP'),
    ];

    // Additional codes for American Express.
    $amex_codes = [
      'A' => new TranslatableMarkup('Card holder address only correct.'),
      'D' => new TranslatableMarkup('Card holder name incorrect, postal code matches.'),
      'E' => new TranslatableMarkup('Card holder name incorrect, address and postal code match.'),
      'F' => new TranslatableMarkup('Card holder name incorrect, address matches.'),
      'K' => new TranslatableMarkup('Card holder name matches.'),
      'L' => new TranslatableMarkup('Card holder name and postal code match.'),
      'M' => new TranslatableMarkup('Card holder name, address and postal code match.'),
      'N' => new TranslatableMarkup('No, card holder address and postal code are both incorrect.'),
      'O' => new TranslatableMarkup('Card holder name and address match.'),
      'R' => new TranslatableMarkup('System unavailable; retry.'),
      'S' => new TranslatableMarkup('AVS not supported.'),
      'U' => new TranslatableMarkup('Information unavailable.'),
      'W' => new TranslatableMarkup('No, card holder name, address and postal code are all incorrect.'),
      'Y' => new TranslatableMarkup('Yes, card holder address and postal code are both correct.'),
      'Z' => new TranslatableMarkup('Card holder postal code only correct.'),
    ] + $standard_codes;

    return [
      'visa' => $standard_codes,
      'mastercard' => $standard_codes,
      'amex' => $amex_codes,
      'discover' => $standard_codes,
      'maestro' => [
        '0' => new TranslatableMarkup('All the address information matched.'),
        '1' => new TranslatableMarkup('None of the address information matched.'),
        '2' => new TranslatableMarkup('Part of the address information matched.'),
        '3' => new TranslatableMarkup('The merchant did not provide AVS information. Not processed.'),
        '4' => new TranslatableMarkup('Address not checked, or acquirer had no response. Service not available.'),
        'U' => new TranslatableMarkup('Address not checked, or acquirer had no response. Service not available.'),
      ],
    ];
  }

}
