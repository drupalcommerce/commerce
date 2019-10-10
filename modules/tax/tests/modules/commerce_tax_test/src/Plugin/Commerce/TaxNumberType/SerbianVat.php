<?php

namespace Drupal\commerce_tax_test\Plugin\Commerce\TaxNumberType;

use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\TaxNumberTypeWithVerificationBase;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;

/**
 * Provides the Serbian VAT tax number type.
 *
 * Used for testing purposes, none of the rules are real.
 *
 * @CommerceTaxNumberType(
 *   id = "serbian_vat",
 *   label = "Serbian VAT",
 *   countries = {"RS"},
 *   examples = {"901"}
 * )
 */
class SerbianVat extends TaxNumberTypeWithVerificationBase {

  /**
   * {@inheritdoc}
   */
  public function validate($tax_number) {
    // All numbers smaller than 1000 are valid.
    return is_numeric($tax_number) && $tax_number < 1000;
  }

  /**
   * {@inheritdoc}
   */
  public function doVerify($tax_number) {
    $timestamp = $this->time->getRequestTime();
    if ($tax_number == '190') {
      // Simulate the remote service being unavailable.
      return VerificationResult::unknown($timestamp, ['error' => 'http_429']);
    }

    if ($tax_number % 2 === 0) {
      // Even numbers cannot be verified.
      return VerificationResult::failure($timestamp);
    }
    else {
      return VerificationResult::success($timestamp, [
        'name' => 'John Smith',
        'nonce' => mt_rand(0, 1000),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renderVerificationResult(VerificationResult $result) {
    $errors = [
      'http_429' => $this->t('Too many requests.'),
    ];
    $data = $result->getData();

    $element = [];
    if (isset($data['error'])) {
      $error = $data['error'];

      $element['error'] = [
        '#type' => 'item',
        '#title' => $this->t('Error'),
        '#plain_text' => isset($errors[$error]) ? $errors[$error] : $error,
      ];
    }
    if (isset($data['name'])) {
      $element['name'] = [
        '#type' => 'item',
        '#title' => $this->t('Name'),
        '#plain_text' => $data['name'],
      ];
    }

    return $element;
  }

}
