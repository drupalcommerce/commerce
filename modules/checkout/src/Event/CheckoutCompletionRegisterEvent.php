<?php

namespace Drupal\commerce_checkout\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the checkout completion register event.
 *
 * @see \Drupal\commerce_checkout\Event\CheckoutEvents
 */
class CheckoutCompletionRegisterEvent extends Event {

  /**
   * The created account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The checkout order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The redirect URL.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirect;

  /**
   * Constructs a new CheckoutCompletionRegisterEvent object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The created account.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The checkout order.
   */
  public function __construct(AccountInterface $account, OrderInterface $order) {
    $this->account = $account;
    $this->order = $order;
  }

  /**
   * Gets the created account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The created account.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets the checkout order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The checkout order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the redirect URL.
   *
   * Used to redirect the customer after the account has been created.
   *
   * @return \Drupal\Core\Url|null
   *   The redirect url, if set. NULL otherwise.
   */
  public function getRedirectUrl() {
    return $this->redirect;
  }

  /**
   * Sets the redirect URL.
   *
   * @param \Drupal\Core\Url $url
   *   The redirect URL.
   *
   * @return $this
   */
  public function setRedirectUrl(Url $url) {
    $this->redirect = $url;
    return $this;
  }

  /**
   * Sets the redirect.
   *
   * @param string $route_name
   *   The name of the route.
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options. See
   *   \Drupal\Core\Url for the available keys.
   *
   * @return $this
   */
  public function setRedirect($route_name, array $route_parameters = [], array $options = []) {
    $url = new Url($route_name, $route_parameters, $options);
    return $this->setRedirectUrl($url);
  }

}
