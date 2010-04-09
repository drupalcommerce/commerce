<?php
// $Id$

/**
 * @file
 * The commerce price object is responsible for tracking all modififications
 * to a price. The current live price can be read from $object->price, however
 * the price can only be changed through the modifyPrice() method. This ensures
 * that price changes are recorded.
 */

class CommercePrice {
  // Initialize base variables
  protected $price = 0;
  protected $basePrice = 0;
  protected $currency = 'usd';
  protected $log = array();

  function __constructor($amount = 0, $currency = NULL) {
    if (is_numeric($amount)) {
      $this->basePrice = $amount;
    }
    if (empty($currency)) {
      $this->currency = 'usd';
    }
    $this->modifyPrice($this->$basePrice, 'commercePrice', t('Base price'));
  }

  /**
   * function modify_price() is a method to allow users to adjust the current
   * price.
   *
   * @param $amount
   *   The amount of the adjustment.
   *
   * @param $module
   *   The name of the module adjusting the price.  i.e. commerce_tax.
   *
   * @return
   *   TRUE for success, otherwise FALSE.
   */
  function modifyPrice($amount, $module, $description) {
    if (is_numeric($amount)) {
      $this->log[] = array(
        'module' => $module,
        'description' => $description,
        'amount' => $amount,
      );
      $this->price += $amount;
      return TRUE;
    }
    else {
      // Submit a watchdog error if there is not enough data to change the price.
      watchdog('error', 'Attempt to modify a price by a non-numeric value. %module - %description - %amount', array('%module' => $module, '%amount' => $amount, '%description' => $description));
      return FALSE
    }
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
   *
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