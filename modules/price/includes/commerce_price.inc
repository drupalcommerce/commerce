<?php
// $Id$

/**
 * @file
 * Contains the CommercePrice object used for tracking modifications to prices.
 *
 * The CommercePrice object is responsible for tracking all modififications to a
 * price. The current live price can be read from $object->price, however the
 * price can only be changed through the modifyPrice() method. This ensures that
 * price changes are recorded.
 */

class CommercePrice {
  // Initialize base variables.
  protected $price = 0;
  protected $base_price = 0;
  protected $currency_code = '';
  protected $base_currency_code = '';
  protected $log = array();

  function __construct($price = 0, $currency_code = NULL) {
    if (is_numeric($price)) {
      $this->base_price = $price;
    }

    if (empty($currency_code)) {
      $currency_code = commerce_default_currency();
    }

    $this->base_currency_code = $currency_code;

    $this->modifyPrice('commerce_price', t('Base price'), $this->base_price, $this->base_currency_code);
  }

  /**
   * Modifies a price up or down and logs the adjustment.
   *
   * @param $module
   *   The name of the module adjusting the price.  i.e. commerce_tax.
   * @param $description
   *   A short description of the nature of the adjustment.
   * @param $amount
   *   The amount of the adjustment.
   * @param $currency_code
   *   Optionally set or update the currency code for the price.
   *
   * @return
   *   TRUE for success, otherwise FALSE.
   */
  function modifyPrice($module, $description, $amount, $currency_code = NULL) {
    if (is_numeric($amount)) {
      $this->price += $amount;

      $log = array(
        'module' => $module,
        'description' => $description,
        'amount' => $amount,
      );

      if (!empty($currency_code)) {
        $this->currency_code = $currency_code;
        $log['currency_code'] = $currency_code;
      }

      $this->log[] = $log;

      return TRUE;
    }
    else {
      // Submit a watchdog error if there is not enough data to change the price.
      watchdog('error', 'Attempt to modify a price by a non-numeric value. %module - %description - %amount', array('%module' => $module, '%amount' => $amount, '%description' => $description));
      return FALSE;
    }
  }

  /**
   * Returns the current price value.
   */
  function getPrice() {
    return $this->price;
  }

  /**
   * Returns the base price value.
   */
  function getBasePrice() {
    return $this->base_price;
  }

  /**
   * Returns the current currency code.
   */
  function getCurrencyCode() {
    return $this->currency_code;
  }

  /**
   * Returns the basecurrency code.
   */
  function getBaseCurrencyCode() {
    return $this->base_currency_code;
  }

  /**
   * function getLogByModule() allows a developer to load an array of the
   * price modifications from a specified module. i.e. load all of the price
   * adjustments submitted by the tax module.
   *
   * @param $module
   *   The name of the module.
   *
   * @return
   *   An array of price log events.
   */
  function getLogByModule($module) {
    $return = array();
    if (!empty($module)) {
      foreach ($log as $key => $value) {
        if ($module == $value['module']) {
          $return[] = $value;
        }
      }
    }
    return $return;
  }

  /**
   * function getLogByModuleDescription() allows a deveoper to load an array of
   * price modifications which match a specified module and description.  i.e.
   * return all of the log entries from that tax system, for the "VAT 19.5%"
   * tax rate.
   *
   * @param $module
   *   The name of the module.
   * @param $description
   *   The description to filter by.
   *
   * @return
   *   An array of price log events.
   */
  function getLogByDescription($module, $description) {
    $return = array();
    if (!empty($module) && !empty($description)) {
      foreach ($log as $key => $value) {
        if ($module == $value['module'] && $description == $value['description']) {
          $return[] = $value;
        }
      }
    }
    return $return;
  }
}
