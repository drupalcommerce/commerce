<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

/**
 * Provides the European Union VAT tax number type.
 *
 * Note that in addition to EU members, the country list also includes
 * Isle of Man (IM), which uses GB VAT, and Monaco (MC), which uses FR VAT.
 *
 * @CommerceTaxNumberType(
 *   id = "european_union_vat",
 *   label = "European Union VAT",
 *   countries = {
 *     "EU",
 *     "AT", "BE", "BG", "CY", "CZ", "DE", "DK", "EE", "ES", "FI",
 *     "FR", "GB", "GR", "HR", "HU", "IE", "IM", "IT", "LT", "LU",
 *     "LV", "MC", "MT", "NL", "PL", "PT", "RO", "SE", "SI", "SK",
 *   },
 *   examples = {"DE123456789", "HU12345678"}
 * )
 */
class EuropeanUnionVat extends TaxNumberTypeWithVerificationBase {

  /**
   * The SOAP client for VIES (VAT Information Exchange System).
   *
   * @var \SoapClient
   */
  protected $soapClient;

  /**
   * {@inheritdoc}
   */
  public function validate($tax_number) {
    $patterns = $this->getValidationPatterns();
    $prefix = substr($tax_number, 0, 2);
    if (!isset($patterns[$prefix])) {
      return FALSE;
    }
    $number = substr($tax_number, 2);
    if (!preg_match('/^' . $patterns[$prefix] . '$/', $number)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doVerify($tax_number) {
    $time = $this->time->getRequestTime();
    // The SOAP extension is not a Commerce requirement, since only this
    // plugin needs it. The check is skipped in test environments because
    // a mock client will be used instead.
    if (!extension_loaded('soap') && !drupal_valid_test_ua()) {
      return VerificationResult::failure($time, ['error' => 'no_extension']);
    }
    $patterns = $this->getValidationPatterns();
    $prefix = substr($tax_number, 0, 2);
    if (!isset($patterns[$prefix])) {
      return VerificationResult::failure($time, ['error' => 'invalid_number']);
    }
    $number = substr($tax_number, 2);

    try {
      $parameters = [
        'countryCode' => $prefix,
        'vatNumber' => $number,
      ];
      $soap_client = $this->getSoapClient();
      $result = $soap_client->__soapCall('checkVat', [$parameters]);
    }
    catch (\SoapFault $e) {
      return VerificationResult::unknown($time, ['error' => $e->faultstring]);
    }

    if ($result->valid) {
      return VerificationResult::success($time, [
        'name' => $result->name,
        'address' => $result->address,
      ]);
    }
    else {
      return VerificationResult::failure($time);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renderVerificationResult(VerificationResult $result) {
    $errors = [
      // Plugin errors.
      'no_extension' => $this->t('The SOAP PHP extension is missing from the server.'),
      'invalid_number' => $this->t('The tax number is not in the right format.'),
      // VIES errors, as defined in the WSDL.
      'INVALID_INPUT' => $this->t('The tax number is not in the right format.'),
      'GLOBAL_MAX_CONCURRENT_REQ' => $this->t('The remote service is temporarily busy. Error code: GLOBAL_MAX_CONCURRENT_REQ.'),
      'MS_MAX_CONCURRENT_REQ' => $this->t('The remote service is temporarily busy. Error code: MS_MAX_CONCURRENT_REQ.'),
      'SERVICE_UNAVAILABLE' => $this->t('The remote service is temporarily unavailable. Error code: SERVICE_UNAVAILABLE.'),
      'MS_UNAVAILABLE' => $this->t('The remote service is temporarily unavailable. Error code: MS_UNAVAILABLE.'),
      'TIMEOUT' => $this->t('The remote service did not reply within the allocated time period.'),
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
    if (isset($data['address'])) {
      $element['address'] = [
        '#type' => 'item',
        '#title' => $this->t('Address'),
        '#plain_text' => $data['address'],
      ];
    }

    return $element;
  }

  /**
   * Gets the validation patterns.
   *
   * Source: http://ec.europa.eu/taxation_customs/vies/faq.html#item_11
   *
   * @return array
   *   The validation patterns, keyed by prefix.
   *   The prefix is an ISO country code, except for Greece (EL instead of GR).
   */
  protected function getValidationPatterns() {
    $patterns = [
      'AT' => 'U[A-Z\d]{8}',
      'BE' => '(0\d{9}|\d{10})',
      'BG' => '\d{9,10}',
      'CY' => '\d{8}[A-Z]',
      'CZ' => '\d{8,10}',
      'DE' => '\d{9}',
      'DK' => '\d{8}',
      'EE' => '\d{9}',
      'EL' => '\d{9}',
      'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}',
      'FI' => '\d{8}',
      'FR' => '[0-9A-Z]{2}\d{9}',
      'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',
      'HR' => '\d{11}',
      'HU' => '\d{8}',
      'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',
      'IT' => '\d{11}',
      'LT' => '(\d{9}|\d{12})',
      'LU' => '\d{8}',
      'LV' => '\d{11}',
      'MT' => '\d{8}',
      'NL' => '\d{9}B\d{2}',
      'PL' => '\d{10}',
      'PT' => '\d{9}',
      'RO' => '\d{2,10}',
      'SE' => '\d{12}',
      'SI' => '\d{8}',
      'SK' => '\d{10}',
    ];

    return $patterns;
  }

  /**
   * Gets the SOAP client for VIES.
   *
   * @return \SoapClient
   *   The SOAP client.
   */
  protected function getSoapClient() {
    if (!$this->soapClient) {
      ini_set('default_socket_timeout', 10);
      $wsdl = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
      $this->soapClient = new \SoapClient($wsdl, ['exceptions' => TRUE]);
    }

    return $this->soapClient;
  }

}
