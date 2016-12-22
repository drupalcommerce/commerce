<?php

namespace Drupal\commerce_checkout\Controller;

use Drupal\commerce_cart\CartSession;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the checkout form page.
 */
class CheckoutController implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * Constructs a new CheckoutController object.
   *
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(CheckoutOrderManagerInterface $checkout_order_manager, FormBuilderInterface $form_builder, CartSessionInterface $cart_session) {
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->formBuilder = $form_builder;
    $this->cartSession = $cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('form_builder'),
      $container->get('commerce_cart.cart_session')
    );
  }

  /**
   * Builds and processes the form provided by the order's checkout flow.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The render form.
   */
  public function formPage(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);

    // Determine the order's step. If the order does not have access to the
    // requested step, it will be redirected appropriately.
    $order_step = $this->selectCheckoutStep($order);
    if ($checkout_flow->getPlugin()->getStepId() !== $order_step) {
      $url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $order->id(), 'step' => $order_step]);
      return new RedirectResponse($url->toString());
    }

    $form_state = new FormState();
    return $this->formBuilder->buildForm($checkout_flow->getPlugin(), $form_state);
  }

  /**
   * Checks access for the form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    // Check if the order has been canceled.
    if ($order->getState()->value == 'canceled') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    // The user can checkout only their own non-empty orders.
    if ($account->isAuthenticated()) {
      $customer_check = $account->id() == $order->getCustomerId();
    }
    else {
      $active_cart = $this->cartSession->hasCartId($order->id(), CartSession::ACTIVE);
      $completed_cart = $this->cartSession->hasCartId($order->id(), CartSession::COMPLETED);
      $customer_check = $active_cart || $completed_cart;
    }

    $access = AccessResult::allowedIf($customer_check)
      ->andIf(AccessResult::allowedIf($order->hasItems()))
      ->andIf(AccessResult::allowedIfHasPermission($account, 'access checkout'))
      ->addCacheableDependency($order);
    return $access;
  }

  /**
   * Checks access to a particular checkout page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE or FALSE indicating access.
   */
  protected function selectCheckoutStep(OrderInterface $order) {
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    $requested_step = $checkout_flow->getPlugin()->getStepId();
    $order_step = $order->checkout_step->value;
    // An empty $order_step means the checkout flow is at the first step.
    if (empty($order_step)) {
      $visible_steps = $checkout_flow->getPlugin()->getVisibleSteps();
      $visible_step_ids = array_keys($visible_steps);
      $order_step = reset($visible_step_ids);
    }

    $order_state = $order->getState()->value;

    // An order is expected to be a draft throughout checkout, unless it has
    // been placed, in which it can view the complete step.
    if ($order_state != 'draft' && $requested_step == 'complete') {
      return $requested_step;
    }
    // The order is a draft, continue other logic checks.
    else {
      // Draft orders cannot reach the complete step.
      if ($requested_step == 'complete') {
        return $order_step;
      }
    }
    return $order_step;
  }

}
