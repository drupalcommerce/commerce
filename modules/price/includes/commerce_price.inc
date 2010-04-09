<?php
// $Id$

/**
 * @file
 * The commerce price object is responsible for tracking all modififications
 * to a price. The current live price can be read from $object->price, however
 * the price can only be changed through the modifyPrice() method. This ensures
 * that price changes are recorded.
 */

class commercePrice {
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
  function modifyPrice($amount = 0, $module = '', $description = '') {
    if (is_numeric($amount) && !empty($module) && !empty($description)) {
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
      watchdog('error', '%module could not successfully modify the price by %amount for %description', array('%module' => $module, '%amount' => $amount, '%description' => $description));
      return FALSE
    }
  }
  
  function getLogByModule($module) {
    $return = array();
    foreach ($log as $key => $value) {
      if ($module == $value['module']) {
        $return[] = $value;
      }
    }
    return $return;
  }
  
  function getLogByDescription($module, $description) {
    $return = array();
    foreach ($log as $key => $value) {
      if ($module == $value['module'] && $description == $value['description']) {
        $return[] = $value;
      }
    }
    return $return;
  }
}