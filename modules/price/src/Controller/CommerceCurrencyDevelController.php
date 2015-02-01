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
   * @param \CommerceGuys\Intl\Currency\CurrencyInterface $commerce_currency
   *
   * @return string
   */
  public function currencyLoad(CurrencyInterface $commerce_currency) {
    return $this->loadObject('commerce_currency', $commerce_currency);
  }

}
