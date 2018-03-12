<?php

namespace Drupal\commerce_promotion;

/**
 * Represents a coupon code pattern.
 *
 * Coupon code patterns are passed to the coupon code generator.
 *
 * @see \Drupal\commerce_promotion\CouponCodeGeneratorInterface
 */
final class CouponCodePattern {

  // Pattern types.
  const ALPHANUMERIC = 'alphanumeric';
  const ALPHABETIC = 'alphabetic';
  const NUMERIC = 'numeric';

  /**
   * The pattern type.
   *
   * @var string
   */
  protected $type;

  /**
   * The prefix.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The suffix.
   *
   * @var string
   */
  protected $suffix;

  /**
   * The length.
   *
   * @var int
   */
  protected $length;

  /**
   * Constructs a new CouponCodePattern object.
   *
   * @param string $type
   *   The pattern type.
   * @param string $prefix
   *   The prefix.
   * @param string $suffix
   *   The suffix.
   * @param int $length
   *   The length.
   */
  public function __construct($type, $prefix = '', $suffix = '', $length = 8) {
    $pattern_types = [self::ALPHANUMERIC, self::ALPHABETIC, self::NUMERIC];
    if (!in_array($type, $pattern_types)) {
      throw new \InvalidArgumentException(sprintf('Unknown pattern type "$s".', $type));
    }

    $this->type = $type;
    $this->prefix = $prefix;
    $this->suffix = $suffix;
    $this->length = $length;
  }

  /**
   * Gets the pattern type.
   *
   * @return string
   *   The pattern type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Gets the prefix.
   *
   * @return string
   *   The prefix.
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * Gets the suffix.
   *
   * @return string
   *   The suffix.
   */
  public function getSuffix() {
    return $this->suffix;
  }

  /**
   * Gets the length.
   *
   * @return int
   *   The length.
   */
  public function getLength() {
    return $this->length;
  }

}
