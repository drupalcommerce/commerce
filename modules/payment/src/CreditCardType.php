<?php

namespace Drupal\commerce_payment;

/**
 * Represents a credit card type.
 */
final class CreditCardType {

  /**
   * The credit card type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The credit card type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The credit card type number prefixes.
   *
   * @var array
   */
  protected $numberPrefixes;

  /**
   * The credit card type number lengths.
   *
   * @var array
   */
  protected $numberLengths = [16];

  /**
   * The credit card type security code length.
   *
   * @var string
   */
  protected $securityCodeLength = 3;

  /**
   * Whether the credit cart type uses Luhn validation.
   *
   * @var string
   */
  protected $usesLuhn = TRUE;

  /**
   * Constructs a new CreditCardType instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'number_prefixes'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->numberPrefixes = $definition['number_prefixes'];
    if (isset($definition['number_lengths'])) {
      $this->numberLengths = $definition['number_lengths'];
    }
    if (isset($definition['security_code_length'])) {
      $this->securityCodeLength = $definition['security_code_length'];
    }
    if (isset($definition['uses_luhn'])) {
      $this->usesLuhn = $definition['uses_luhn'];
    }
  }

  /**
   * Gets the credit card type ID.
   *
   * @return string
   *   The credit card type ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the credit card type label.
   *
   * @return string
   *   The credit card type label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the credit card type number prefixes.
   *
   * @return array
   *   The credit card type number prefixes.
   */
  public function getNumberPrefixes() {
    return $this->numberPrefixes;
  }

  /**
   * Gets the credit card type number lengths.
   *
   * @return array
   *   The credit card type number lengths.
   */
  public function getNumberLengths() {
    return $this->numberLengths;
  }

  /**
   * Gets the credit card type security code length.
   *
   * @return string
   *   The credit card type security code length.
   */
  public function getSecurityCodeLength() {
    return $this->securityCodeLength;
  }

  /**
   * Gets whether the credit card type uses Luhn validation.
   *
   * @return bool
   *   TRUE if the credit card type uses Luhn validation, FALSE otherwise.
   */
  public function usesLuhn() {
    return $this->usesLuhn;
  }

}
