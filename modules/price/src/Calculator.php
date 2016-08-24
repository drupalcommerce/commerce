<?php

namespace Drupal\commerce_price;

/**
 * A utility class to handle simple arithmetic using bcmath.
 */
class Calculator {

  /**
   * Compare two numeric string values together.
   *
   * @param string $operandA
   *   A numeric string value.
   * @param string $operandB
   *   A numeric string value.
   * @param int $scale
   *   The number of digits after the decimal place in the result.
   *
   * @return int
   *   A value of 0, if both prices are equal, 1, if the current price is greater
   *   than the price being compared to and -1, if the current price is less than
   *   the price being compared to.
   */
  public static function compare($operandA, $operandB, $scale = 6) {
    return bccomp($operandA, $operandB, $scale);
  }

  /**
   * Add two numeric string values together.
   *
   * @param string $operandA
   *   A numeric string value.
   * @param string $operandB
   *   A numeric string value.
   * @param int $scale
   *   The number of digits after the decimal place in the result.
   *
   * @return string
   *   The added value of operandA and operandB.
   */
  public static function add($operandA, $operandB, $scale = 6) {
    return bcadd($operandA, $operandB, $scale);
  }

  /**
   * Subtract one numeric string value from another.
   *
   * @param string $operandA
   *   A numeric string value.
   * @param string $operandB
   *   A numeric string value.
   * @param int $scale
   *   The number of digits after the decimal place in the result.
   *
   * @return string
   *   The subtracted value of operandA and operandB.
   */
  public static function subtract($operandA, $operandB, $scale = 6) {
    return bcsub($operandA, $operandB, $scale);
  }

  /**
   * Multiply two numeric string values together.
   *
   * @param string $operandA
   *   A numeric string value.
   * @param string $operandB
   *   A numeric string value.
   * @param int $scale
   *   The number of digits after the decimal place in the result.
   *
   * @return string
   *   The multiplied value of operandA and operandB.
   */
  public static function multiply($operandA, $operandB, $scale = 6) {
    return bcmul($operandA, $operandB, $scale);
  }

  /**
   * Divide one numeric string value by another.
   *
   * @param string $operandA
   *   A numeric string value.
   * @param string $operandB
   *   A numeric string value.
   * @param int $scale
   *   The number of digits after the decimal place in the result.
   *
   * @return string
   *   The divided value of operandA and operandB.
   */
  public static function divide($operandA, $operandB, $scale = 6) {
    return bcdiv($operandA, $operandB, $scale);
  }

  /**
   * Calculates the ceil value of a numeric string.
   *
   * @param string $operand
   *   A numeric string value.
   *
   * @return string
   *   The ceil value of $operand.
   */
  public static function ceil($operand) {
    return (string) ceil($operand);
  }

  /**
   * Calculates the floor value of a numeric string.
   *
   * @param string $operand
   *   A numeric string value.
   *
   * @return string
   *   The floor value of $operand.
   */
  public static function floor($operand) {
    return (string) floor($operand);
  }

  /**
   * Assert that a value is a non-float, numeric string value.
   *
   * @param string $operand
   *   A value to check against.
   *
   * @throws \InvalidArgumentException
   *    When format found to be non-string or non-numeric value.
   */
  public static function assertAmountFormat($operand) {

    if (is_float($operand)) {
      throw new \InvalidArgumentException(sprintf('The provided amount "%s" must be a string, not a float.', $operand));
    }
    if (!is_numeric($operand)) {
      throw new \InvalidArgumentException(sprintf('The provided amount "%s" must be a valid string.', $operand));
    }
  }

}
