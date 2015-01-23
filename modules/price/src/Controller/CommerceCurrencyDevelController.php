<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Controller\CommerceCurrencyDevelController.
 */

namespace Drupal\commerce_price\Controller;

use CommerceGuys\Intl\Currency\CurrencyInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce Currency devel routes.
 */
class CommerceCurrencyDevelController extends DevelController {

  /**
   * Dump devel information for a commerce_currency entity.
   *
   * @param \CommerceGuys\Intl\Currency\CurrencyInterface $commerceCurrency
   *
   * @return string
   */
  public function currencyLoad(CurrencyInterface $commerceCurrency) {
    return $this->loadObject('commerce_currency', $commerceCurrency);
  }

}
