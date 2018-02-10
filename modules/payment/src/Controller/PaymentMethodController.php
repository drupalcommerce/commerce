<?php

namespace Drupal\commerce_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns responses for PaymentMethodController routes.
 */
class PaymentMethodController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Mark payment method as default.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the user payment methods listing.
   */
  public function setDefault(RouteMatchInterface $routeMatch) {
    $payment_method = $routeMatch->getParameter('commerce_payment_method');
    $payment_method->setDefault(TRUE);
    $payment_method->save();

    drupal_set_message($this->t('The %label payment method has been marked as default.', ['%label' => $payment_method->label()]));

    /** @var \Drupal\Core\Url $url */
    $url = $payment_method->urlInfo('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
