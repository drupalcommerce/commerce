<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Controller\CurrencyController.
 */

namespace Drupal\commerce_price\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Route controller for currencies.
 */
class CurrencyController extends ControllerBase {

  /**
   * Performs an operation on the currency entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the currency listing.
   */
  public function performOperation(RouteMatchInterface $routeMatch) {
    $currency = $routeMatch->getParameter('commerce_currency');
    $op = $routeMatch->getParameter('op');
    $currency->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label currency has been enabled.', ['%label' => $currency->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label currency has been disabled.', ['%label' => $currency->label()]));
    }

    $url = $currency->urlInfo('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
