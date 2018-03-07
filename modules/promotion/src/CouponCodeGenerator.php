<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Database\Connection;

class CouponCodeGenerator implements CouponCodeGeneratorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new CouponCodeGenerator object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePattern(CouponCodePattern $pattern, $quantity = 1) {
    $character_set = $this->getCharacterSet($pattern->getType());
    $combinations = pow(count($character_set), $pattern->getLength());
    return !($quantity > $combinations);
  }

  /**
   * {@inheritdoc}
   */
  public function generateCodes(CouponCodePattern $pattern, $quantity = 1) {
    $character_set = $this->getCharacterSet($pattern->getType());
    $character_set_size = count($character_set);
    $length = $pattern->getLength();
    $prefix = $pattern->getPrefix();
    $suffix = $pattern->getSuffix();

    // Generate twice the requested quantity, to improve chances of having
    // the needed quantity after removing non-unique/existing codes.
    $codes = [];
    for ($i = 0; $i < ($quantity * 2); $i++) {
      $code = '';
      while (strlen($code) < $length) {
        $random_index = mt_rand(0, $character_set_size - 1);
        $code .= $character_set[$random_index];
      }
      $codes[] = $prefix . $code . $suffix;
    }
    $codes = array_unique($codes);

    // Remove codes which already exist in the database.
    $result = $this->connection->select('commerce_promotion_coupon', 'c')
      ->fields('c', ['code'])
      ->condition('code', $codes, 'IN')
      ->execute();
    $existing_codes = $result->fetchCol();
    $codes = array_diff($codes, $existing_codes);

    return array_slice($codes, 0, $quantity);
  }

  /**
   * Gets the character set for the given pattern type.
   *
   * @param string $pattern_type
   *   The pattern type.
   *
   * @return string[]
   *   The character set.
   */
  protected function getCharacterSet($pattern_type) {
    $characters = [];
    switch ($pattern_type) {
      // No 'I', 'O', 'i', 'l', '0', '1' to avoid recognition issues.
      case CouponCodePattern::ALPHANUMERIC:
        $characters = [
          'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M',
          'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
          'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'm', 'n',
          'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
          '2', '3', '4', '5', '6', '7', '8', '9',
        ];
        break;

      case CouponCodePattern::ALPHABETIC:
        // No 'I', 'i', 'l' to avoid recognition issues.
        $characters = [
          'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M',
          'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
          'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'm', 'n',
          'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        ];
        break;

      case CouponCodePattern::NUMERIC:
        $characters = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    }

    return $characters;
  }

}
